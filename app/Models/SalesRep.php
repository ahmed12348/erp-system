<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRep extends Model
{
    use RecordsActivity;

    /** اسم السجل في سجل النشاط */
    protected $activityLog = 'rep';

    /** الحقول اللي بيتسجّل تغيّرها — مش كل الأعمدة */
    protected $activityFields = ['name', 'commission_rate', 'is_active'];

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

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
