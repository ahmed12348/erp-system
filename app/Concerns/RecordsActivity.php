<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * تسجيل النشاط على موديل.
 *
 * بيلفّ `LogsActivity` بتاع spatie بإعداداتنا عشان ما نكرّرش نفس
 * السطور في كل موديل:
 *
 *  - الحقول المسجّلة محدودة بـ `$activityFields` — مش كل الأعمدة.
 *    سجل النشاط بيكبر أسرع من أي جدول تاني، وتخزين كل عمود في كل
 *    تعديل بيملا القرص بحاجات محدش بيسأل عنها (`updated_at` مثلاً).
 *  - `logOnlyDirty` + `dontSubmitEmptyLogs`: حفظ من غير تغيير فعلي
 *    ما بيسجّلش سطر فاضي.
 *  - `log_name` بيبقى اسم الكيان بالعربي في الواجهة عن طريق
 *    `ActivityLabels`.
 *
 * الموديل بيحدّد `$activityLog` (اسم السجل) و`$activityFields`.
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->activityLogName())
            ->logOnly($this->activityFields())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            // الوصف نص إنجليزي قصير؛ العرض العربي بيتبني في الواجهة
            ->setDescriptionForEvent(fn (string $event): string => $event);
    }

    /**
     * حدث مخصّص على الموديل ده — زي تأكيد فاتورة أو إرسالها للمنظومة.
     *
     * @param  array<string, mixed>  $properties
     */
    public function recordActivity(string $event, array $properties = []): void
    {
        activity($this->activityLogName())
            ->performedOn($this)
            ->causedBy(auth()->user() ?? auth('api')->user())
            ->event($event)
            ->withProperties($properties)
            ->log($event);
    }

    protected function activityLogName(): string
    {
        return property_exists($this, 'activityLog')
            ? $this->activityLog
            : str($this->getMorphClass())->classBasename()->snake()->toString();
    }

    /**
     * @return array<int, string>
     */
    protected function activityFields(): array
    {
        /** @var array<int, string> $fields */
        $fields = property_exists($this, 'activityFields')
            ? $this->activityFields
            : ($this instanceof Model ? $this->getFillable() : []);

        return $fields;
    }
}
