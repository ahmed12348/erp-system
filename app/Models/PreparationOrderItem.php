<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreparationOrderItem extends Model
{
    protected $fillable = ['preparation_order_id', 'product_id', 'quantity', 'prepared_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'prepared_quantity' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<PreparationOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PreparationOrder::class, 'preparation_order_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * الكمية اللي بتتحجز فعلاً وبتروح للفاتورة.
     *
     * المخزن ساعات بيلاقي الصنف ناقص فبيعدّل المحضّر؛ وقتها المحضّر هو
     * الحقيقة. وقبل ما يعدّل حاجة، المطلوب هو الحقيقة.
     */
    public function effectiveQuantity(): float
    {
        return (float) ($this->prepared_quantity ?? $this->quantity);
    }
}
