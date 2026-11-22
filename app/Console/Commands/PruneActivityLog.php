<?php

namespace App\Console\Commands;

use App\Services\ActivityService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * بيقلّم سجل النشاط.
 *
 * السجل بيكبر أسرع من أي جدول تاني في النظام، والاستضافة المشتركة
 * مساحتها محدودة. بنحتفظ بسنة — كفاية لأي مراجعة إدارية — واللي أقدم
 * من كده بيتمسح.
 *
 * بيمسح على دفعات عشان ما يقفلش الجدول على استضافة ضعيفة.
 */
class PruneActivityLog extends Command
{
    protected $signature = 'erp:prune-activity
        {--months= : عدد الشهور اللي بتتحفظ (الافتراضي 12)}
        {--dry-run : بيعدّ بس من غير ما يمسح}';

    protected $description = 'بيمسح سجل النشاط الأقدم من المدة المحددة';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: ActivityService::RETENTION_MONTHS);

        if ($months < 1) {
            $this->error('عدد الشهور لازم يكون 1 على الأقل.');

            return self::FAILURE;
        }

        $cutoff = now()->subMonths($months);

        $query = Activity::query()->where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) {
            $this->info('مفيش حاجة تتمسح — السجل كله أحدث من '.$months.' شهر.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info($count.' سطر أقدم من '.$cutoff->toDateString().' (تجربة — مفيش حاجة اتمسحت).');

            return self::SUCCESS;
        }

        // على دفعات عشان ما نقفلش الجدول
        $deleted = 0;

        do {
            $chunk = Activity::query()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $deleted += $chunk;
        } while ($chunk > 0);

        $this->info('اتمسح '.$deleted.' سطر أقدم من '.$cutoff->toDateString().'.');

        return self::SUCCESS;
    }
}
