<?php

namespace App\Http\Controllers\Ai;

use App\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScanInvoiceRequest;
use App\Services\InvoiceScanService;
use Illuminate\Http\JsonResponse;

/**
 * «مسح فاتورة» — بيقرا الصورة ويرجّع بيانات الفورم.
 *
 * بيرجّع JSON بس. مفيش أي كتابة في قاعدة البيانات هنا: المستخدم
 * بيراجع اللي اتملا وبيحفظ من زرار الحفظ العادي، والملف نفسه بيترفع
 * تاني مع الحفظ عشان يتعلّق على الفاتورة.
 */
class ScanInvoiceController extends Controller
{
    public function __construct(private readonly InvoiceScanService $scanner) {}

    public function __invoke(ScanInvoiceRequest $request): JsonResponse
    {
        try {
            $result = $this->scanner->scan(
                $request->file('file'),
                $request->string('type')->toString(),
            );
        } catch (AiUnavailableException $e) {
            return response()->json([
                'message' => $e->arabicMessage,
                'reason' => $e->reason,
            ], 503);
        }

        return response()->json($result);
    }
}
