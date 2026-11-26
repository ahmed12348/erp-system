<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PreparationOrder;
use App\Models\Supplier;
use App\Services\WhatsAppService;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

/**
 * مشاركة مستند على الواتساب.
 *
 * فيه طريقتين، والاتنين من نفس الزرار:
 *
 *  - **فتح الشات** (الافتراضي، شغّال دايمًا): الواجهة بتفتح `wa.me`
 *    على رقم التاجر، والمستخدم هو اللي بيدوس إرسال. مفيش API ولا
 *    توكن ولا قالب معتمد — شغّال من يوم ما ينشر.
 *  - **إرسال مباشر** (هنا): لما مفاتيح Cloud API متظبّطة، الرسالة
 *    بتروح من رقم الشركة من غير ما يتفتح أي حاجة.
 *
 * الكنترولر ده مسؤول عن التانية بس، وعن حفظ رقم التاجر لما يكون
 * ناقص — الأولى كلها في المتصفح.
 */
class WhatsAppShareController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    /**
     * إرسال مستند مباشرة للتاجر.
     *
     * بيرجّع `back()` برسالة — الزرار جوه صفحة والمستخدم واقف عليها.
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:preparation-order,invoice'],
            'id' => ['required', 'integer'],
            // رقم بديل لما التاجر مالوش رقم مسجّل
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (! $this->whatsapp->enabled()) {
            // الزرار أصلاً مابيظهرش — ده حارس تاني لو الطلب جه بإيد
            abort(404);
        }

        $document = $this->document($data['type'], (int) $data['id']);

        $number = PhoneNumber::forWhatsApp(
            $data['phone'] ?? $this->partyPhone($document),
        );

        if ($number === null) {
            return back()->withErrors([
                'whatsapp' => 'مفيش رقم واتساب صالح للطرف ده. اكتب رقم وجرّب تاني.',
            ]);
        }

        $result = $this->whatsapp->sendTemplate(
            $number,
            (string) config('notifications.whatsapp.templates.document'),
            [
                (string) config('app.name'),
                $this->title($document),
                $this->pdfUrl($document),
            ],
        );

        if (! $result['sent']) {
            return back()->withErrors([
                'whatsapp' => $result['error'] ?? 'الإرسال مامشيش.',
            ]);
        }

        /*
         * السجل بيتكتب بعد النجاح بس. تسجيل المحاولة كان هيخلّي
         * السجل يقول «بعت» على رسايل واتساب رفضها.
         */
        $document->recordActivity('whatsapp_sent', [
            'to' => $number,
            'document' => $this->title($document),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتبعت على واتساب ('.$number.').',
        ]);

        return back();
    }

    /**
     * حفظ رقم على التاجر أو المورد من نافذة المشاركة.
     *
     * الرقم بيتحفظ زي ما المستخدم كتبه، مش بصيغته الدولية: ده رقم
     * التاجر في دفتر العناوين وبيتعرض في كل مكان، والشكل المحلي هو
     * اللي الناس بتعرفه. التحويل لواتساب بيحصل وقت الإرسال.
     */
    public function storePhone(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $data = $request->validate([
            'party' => ['required', 'in:customer,supplier'],
            'id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:30'],
        ], [
            'phone.required' => 'اكتب رقم التليفون.',
        ]);

        if (! PhoneNumber::hasWhatsApp($data['phone'])) {
            return back()->withErrors([
                'phone' => 'الرقم ده مش شكله رقم موبايل صالح.',
            ]);
        }

        $party = $data['party'] === 'customer'
            ? Customer::findOrFail($data['id'])
            : Supplier::findOrFail($data['id']);

        $party->update(['phone' => trim($data['phone'])]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتحفظ الرقم على '.$party->name.'.',
        ]);

        return back();
    }

    private function document(string $type, int $id): Invoice|PreparationOrder
    {
        if ($type === 'invoice') {
            $invoice = Invoice::findOrFail($id);

            // المسودّة لسه مش مستند — مفيش حاجة تتبعت
            abort_if($invoice->isDraft(), 404);

            return $invoice;
        }

        return PreparationOrder::findOrFail($id);
    }

    private function partyPhone(Invoice|PreparationOrder $document): ?string
    {
        return $document instanceof Invoice
            ? ($document->customer?->phone ?? $document->supplier?->phone)
            : $document->customer?->phone;
    }

    private function title(Invoice|PreparationOrder $document): string
    {
        if ($document instanceof PreparationOrder) {
            return 'طلب تحضير '.$document->number;
        }

        return match ($document->type) {
            Invoice::TYPE_PURCHASE => 'فاتورة شراء '.$document->number,
            Invoice::TYPE_SALE_RETURN => 'مرتجع بيع '.$document->number,
            Invoice::TYPE_PURCHASE_RETURN => 'مرتجع شرا '.$document->number,
            default => 'فاتورة بيع '.$document->number,
        };
    }

    /** نفس الرابط الموقّع اللي بيروح في المشاركة اليدوية */
    private function pdfUrl(Model $document): string
    {
        return $document instanceof PreparationOrder
            ? URL::temporarySignedRoute(
                'preparation-orders.pdf',
                now()->addWeek(),
                ['preparationOrder' => $document->id],
            )
            : URL::temporarySignedRoute(
                'invoices.pdf',
                now()->addWeek(),
                ['invoice' => $document->id],
            );
    }
}
