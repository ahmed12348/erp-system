<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use RecordsActivity;

    /** اسم السجل في سجل النشاط */
    protected $activityLog = 'warehouse';

    /** الحقول اللي بيتسجّل تغيّرها — مش كل الأعمدة */
    protected $activityFields = ['name', 'location', 'is_active'];

    protected $fillable = ['name', 'location', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
