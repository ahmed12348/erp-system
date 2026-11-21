<?php

namespace App\Concerns;

use App\Models\Invoice;
use App\Services\AttachmentService;
use Illuminate\Http\Request;

/**
 * بيعلّق صورة الفاتورة الممسوحة على الفاتورة بعد ما تتحفظ.
 *
 * الملف بيوصل مع الحفظ العادي مش قبله: لما المستخدم يمسح فاتورة،
 * المتصفح بيفضل ماسك الملف وبيبعته مع الفورم. كده مفيش ملفات مؤقتة
 * على السيرفر ولا مهمة تنضيف — وده مهم على استضافة مشتركة.
 *
 * فشل التعليق مابيوقّعش الحفظ: الفاتورة اتسجّلت وأرقامها دخلت
 * المخزن، فمش منطقي نرجّع كل ده عشان صورة.
 */
trait AttachesScannedInvoice
{
    protected function attachScan(Request $request, Invoice $invoice): void
    {
        if (! $request->hasFile('attachment')) {
            return;
        }

        app(AttachmentService::class)->store(
            $invoice,
            $request->file('attachment'),
            $request->user(),
            'صورة الفاتورة الممسوحة',
        );
    }
}
