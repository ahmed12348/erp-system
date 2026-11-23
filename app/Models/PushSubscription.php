<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * اشتراك متصفح في إشعارات الـ Web Push.
 *
 * المستخدم ممكن يكون مشترك من أكتر من جهاز، فكل جهاز سطر.
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint', 'public_key', 'auth_token', 'content_encoding',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
