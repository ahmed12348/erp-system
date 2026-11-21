<?php

namespace App\Models;

use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * اسم بديل لتاجر — الاسم اللي بيتكتب على الفاتورة مش دايمًا الاسم
 * المسجّل في النظام («وليد الثلاتيني» مقابل «وليد عبد العظيم»).
 */
class CustomerAlias extends Model
{
    protected $fillable = ['customer_id', 'alias', 'user_id'];

    protected static function booted(): void
    {
        $sync = function (self $model): void {
            $model->normalized = ArabicText::normalize($model->alias);
        };

        static::creating($sync);
        static::updating($sync);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
