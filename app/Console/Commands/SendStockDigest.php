<?php

namespace App\Console\Commands;

use App\Services\StockAlertService;
use Illuminate\Console\Command;

/**
 * ملخّص النواقص اليومي.
 *
 * بيتنادى من الجدولة كل يوم الصبح. آمن لو اتشغّل تاني — بيبعت
 * الملخّص تاني وخلاص، مفيش حالة بتتبوّظ.
 */
class SendStockDigest extends Command
{
    protected $signature = 'erp:stock-digest';

    protected $description = 'بيبعت ملخّص الأصناف تحت حد الطلب للمستخدمين المعنيين';

    public function handle(StockAlertService $alerts): int
    {
        $count = $alerts->sendDigest();

        $this->info($count === 0
            ? 'مفيش نواقص أو مفيش مستقبلين — مفيش حاجة اتبعتت.'
            : 'الملخّص اتبعت لـ '.$count.' مستخدم.');

        return self::SUCCESS;
    }
}
