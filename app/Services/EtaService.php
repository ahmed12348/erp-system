<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * إرسال الفاتورة لمنظومة الفاتورة الإلكترونية المصرية (ETA).
 *
 * ⚠️ النسخة دي **محاكاة** — مفيش أي اتصال حقيقي بمنظومة مصلحة الضرائب.
 * بتولّد UUID وهمي وبتحرّك الحالة من "لم تُرسل" لـ "مُرسلة" عشان العرض
 * التوضيحي. ربط المنظومة الحقيقي محتاج توقيع إلكتروني وبيانات اعتماد
 * من المصلحة، وده مكانه الطبيعي هنا جوه الـ service من غير ما تتغيّر
 * أي حاجة في الـ controller أو الواجهة.
 */
class EtaService
{
    /** الحالات اللي ينفع نبعت منها */
    private const SENDABLE = [null, Invoice::ETA_PENDING, Invoice::ETA_REJECTED];

    /**
     * أنواع المستندات في المنظومة.
     *
     * المرتجع بيتبعت **إشعار خصم** (credit note) مش فاتورة: المنظومة
     * بتعتبره مستند مستقل بيشاور على الفاتورة الأصلية بـ UUID بتاعها،
     * وده اللي بيخلّي المصلحة تقفل الاتنين على بعض. فاتورة بقيمة سالبة
     * مش بديل مقبول.
     */
    public const DOC_INVOICE = 'I';

    public const DOC_CREDIT_NOTE = 'C';

    public const DOC_LABELS = [
        self::DOC_INVOICE => 'فاتورة',
        self::DOC_CREDIT_NOTE => 'إشعار خصم',
    ];

    public function send(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->type, [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN], true)) {
            throw ValidationException::withMessages([
                'eta' => 'الفاتورة الإلكترونية بتتطبّق على فواتير البيع ومرتجعاتها بس.',
            ]);
        }

        /*
         * إشعار الخصم لازم يشاور على فاتورة متبعتة للمنظومة.
         *
         * من غير UUID الأصلية، المنظومة مش هتعرف الإشعار ده بيخصم من
         * إيه — فبنوقفه هنا بدل ما يتبعت ويترفض.
         */
        if ($invoice->type === Invoice::TYPE_SALE_RETURN) {
            $original = $invoice->originalInvoice;

            if (! $original?->eta_uuid) {
                throw ValidationException::withMessages([
                    'eta' => 'لازم تبعت الفاتورة الأصلية للمنظومة الأول، عشان إشعار الخصم يشاور عليها.',
                ]);
            }
        }

        if ($invoice->status !== Invoice::STATUS_CONFIRMED) {
            throw ValidationException::withMessages([
                'eta' => 'لازم تأكّد الفاتورة الأول قبل ما تبعتها للمنظومة.',
            ]);
        }

        if (! in_array($invoice->eta_status, self::SENDABLE, true)) {
            throw ValidationException::withMessages([
                'eta' => 'الفاتورة دي اتبعتت للمنظومة قبل كده.',
            ]);
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->forceFill([
                'eta_status' => Invoice::ETA_SENT,
                'eta_uuid' => $this->fakeUuid(),
            ])->save();

            // `eta_status` مش في حقول السجل، فبنسجّل الإرسال كحدث صريح
            $invoice->recordActivity('eta_sent', [
                'number' => $invoice->number,
                'eta_uuid' => $invoice->eta_uuid,
                'document' => self::DOC_LABELS[$this->documentType($invoice)],
            ]);

            return $invoice->fresh();
        });
    }

    /** نوع المستند في المنظومة — فاتورة ولا إشعار خصم */
    public function documentType(Invoice $invoice): string
    {
        return $invoice->type === Invoice::TYPE_SALE_RETURN
            ? self::DOC_CREDIT_NOTE
            : self::DOC_INVOICE;
    }

    /** شكل الـ UUID بتاع المنظومة: ٢٦ حرف hex بحروف كبيرة */
    private function fakeUuid(): string
    {
        return strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 26));
    }
}
