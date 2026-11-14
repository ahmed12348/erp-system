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

    public function send(Invoice $invoice): Invoice
    {
        if ($invoice->type !== Invoice::TYPE_SALE) {
            throw ValidationException::withMessages([
                'eta' => 'الفاتورة الإلكترونية بتتطبّق على فواتير البيع بس.',
            ]);
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

            return $invoice->fresh();
        });
    }

    /** شكل الـ UUID بتاع المنظومة: ٢٦ حرف hex بحروف كبيرة */
    private function fakeUuid(): string
    {
        return strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 26));
    }
}
