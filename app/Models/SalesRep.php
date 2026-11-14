<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRep extends Model
{
    protected $fillable = ['name', 'phone', 'region', 'commission_rate', 'is_active', 'notes'];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
