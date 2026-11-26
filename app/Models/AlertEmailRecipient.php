<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * إيميل بيستقبل تنبيهات المخزون من غير ما يكون مستخدم في النظام.
 *
 * صاحب المحل مش بالضرورة بيفتح النظام كل يوم، وساعات المحاسب الخارجي
 * أو الشريك عايز يتابع من غير ما يبقى له حساب ولا صلاحيات.
 */
class AlertEmailRecipient extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'alert_email';

    /**
     * الحقول اللي بيتسجّل تغيّرها
     *
     * @var array<int, string>
     */
    protected $activityFields = ['email', 'name', 'is_active'];

    protected $fillable = ['email', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
