<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** شرائح السعر المتاحة للتاجر */
    public const TIERS = ['sale', 'wholesale'];

    protected $fillable = [
        'name', 'phone', 'city', 'address', 'price_tier', 'sales_rep_id',
        'credit_limit', 'opening_balance', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(SalesRep::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** مديونية التاجر = افتتاحي + مبيعات - مرتجعات - تحصيلات */
    public function balance(): float
    {
        $confirmed = $this->invoices()->where('status', Invoice::STATUS_CONFIRMED);

        $sales = (clone $confirmed)->where('type', Invoice::TYPE_SALE)->sum('total');
        $returns = (clone $confirmed)->where('type', Invoice::TYPE_SALE_RETURN)->sum('total');
        $paid = $this->payments()->sum('amount');

        return (float) $this->opening_balance + $sales - $returns - $paid;
    }
}
