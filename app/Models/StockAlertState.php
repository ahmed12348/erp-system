<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * حالة تنبيه صنف — بتمنع تكرار نفس التنبيه.
 *
 * من غيرها كل بيعة لصنف ناقص كانت هتبعت تنبيه. بنسجّل إن الصنف
 * «تحت الحد» وما نبعتش تاني غير لما يطلع فوق الحد ويرجع ينزل.
 */
class StockAlertState extends Model
{
    protected $fillable = [
        'product_id', 'below_minimum', 'out_of_stock', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'below_minimum' => 'boolean',
            'out_of_stock' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
