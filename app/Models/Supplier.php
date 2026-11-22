<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use RecordsActivity;

    /** اسم السجل في سجل النشاط */
    protected $activityLog = 'supplier';

    /** الحقول اللي بيتسجّل تغيّرها — مش كل الأعمدة */
    protected $activityFields = ['name', 'phone', 'country', 'currency', 'opening_balance', 'is_active'];

    protected $fillable = ['name', 'phone', 'country', 'currency', 'opening_balance', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'is_active' => 'boolean'];
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
