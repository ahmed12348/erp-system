<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    protected $fillable = ['stock_count_id', 'product_id', 'system_quantity', 'counted_quantity'];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:3',
            'counted_quantity' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<StockCount, $this> */
    public function count(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * الفرق = المعدود ناقص رصيد النظام.
     *
     * موجب يعني فيه زيادة (إذن إضافة)، وسالب يعني ناقص (إذن خصم).
     * `null` معناها البند لسه مااتعدّش — مش صفر.
     */
    public function difference(): ?float
    {
        if ($this->counted_quantity === null) {
            return null;
        }

        return round((float) $this->counted_quantity - (float) $this->system_quantity, 3);
    }
}
