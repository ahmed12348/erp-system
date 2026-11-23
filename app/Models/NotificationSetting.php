<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تفضيلات التنبيهات لمستخدم.
 *
 * عمود JSON واحد شكله `{نوع: {قناة: true}}` — عشان إضافة نوع أو قناة
 * جديدة ما تحتاجش migration.
 */
class NotificationSetting extends Model
{
    protected $fillable = ['user_id', 'channels'];

    protected function casts(): array
    {
        return ['channels' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
