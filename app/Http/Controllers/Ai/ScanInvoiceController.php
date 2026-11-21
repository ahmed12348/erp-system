<?php

namespace App\Http\Controllers\Ai;

use App\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScanInvoiceRequest;
use App\Models\Invoice;
use App\Services\AttachmentService;
use App\Services\InvoiceDraftService;
use App\Services\InvoiceScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * «مسح فاتورة» — بيقرا الصورة ويعمل **مسودة**.
 *
 * المسودة فاتورة بحالة `draft`: ما بتلمسش مخزون ولا أرصدة ولا خزينة
 * ولا عمولات. المستخدم بيتحوّل على صفحتها، يراجع اللي اتقرا ويصحّح
 * المطابقات، وبعدين يضغط «تأكيد الفاتورة» — وساعتها بس بيشتغل مسار
 * الحفظ العادي.
 *
 * الصورة بتتعلّق على المسودة على طول عشان تفضل جنب الفورم وهو بيراجع،
 * وبتنتقل للفاتورة المؤكّدة مع التأكيد.
 */
class ScanInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceScanService $scanner,
        private readonly InvoiceDraftService $drafts,
        private readonly AttachmentService $attachments,
    ) {}

    public function __invoke(ScanInvoiceRequest $request): JsonResponse
    {
        try {
            $scan = $this->scanner->scan(
                $request->file('file'),
                $request->string('type')->toString(),
            );
        } catch (AiUnavailableException $e) {
            return response()->json([
                'message' => $e->arabicMessage,
                'reason' => $e->reason,
            ], 503);
        }

        try {
            $draft = $this->drafts->createFromScan($scan, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'reason' => 'invalid',
            ], 422);
        }

        $this->attachments->store(
            $draft,
            $request->file('file'),
            $request->user(),
            'صورة الفاتورة الممسوحة',
        );

        return response()->json([
            'draftId' => $draft->id,
            'redirect' => route($draft->type === Invoice::TYPE_PURCHASE
                ? 'purchases.drafts.show'
                : 'sales.drafts.show', $draft),
        ]);
    }
}
