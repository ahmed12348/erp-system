<?php

namespace App\Models;

use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * اسم بديل لصنف — زي ما بيتكتب على فواتير العميل بخط اليد.
 *
 * `normalized` بتتحسب لوحدها من `alias` عشان ما تبقاش فيه نسخة
 * متخلّفة عن التانية.
 */
class ProductAlias extends Model
{
    protected $fillable = ['product_id', 'alias', 'user_id'];

    protected static function booted(): void
    {
        $sync = function (self $model): void {
            $model->normalized = ArabicText::normalize($model->alias);
        };

        static::creating($sync);
        static::updating($sync);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
