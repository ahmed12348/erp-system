<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'country', 'currency', 'opening_balance', 'notes'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2'];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** المستحق للمورد = افتتاحي + مشتريات - مرتجعات - مدفوعات */
    public function balance(): float
    {
        $confirmed = $this->invoices()->where('status', Invoice::STATUS_CONFIRMED);

        $purchases = (clone $confirmed)->where('type', Invoice::TYPE_PURCHASE)->sum('total');
        $returns = (clone $confirmed)->where('type', Invoice::TYPE_PURCHASE_RETURN)->sum('total');
        $paid = $this->payments()->sum('amount');

        return (float) $this->opening_balance + $purchases - $returns - $paid;
    }
}
