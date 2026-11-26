<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use RecordsActivity;

    /** اسم السجل في سجل النشاط */
    protected $activityLog = 'product';

    /** الحقول اللي بيتسجّل تغيّرها — مش كل الأعمدة */
    protected $activityFields = ['sku', 'name', 'unit', 'category_id', 'purchase_price', 'sale_price', 'wholesale_price', 'min_stock', 'is_active'];

    /** الوحدات الجاهزة في فورم الصنف — المستخدم يقدر يكتب غيرها */
    public const UNITS = [
        'قطعة', 'كرتونة', 'علبة', 'دستة', 'طقم', 'شيكارة',
        'رول', 'بكرة', 'متر', 'لتر', 'كيلو', 'طن',
    ];

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected $fillable = [
        'category_id', 'sku', 'name', 'unit', 'purchase_price',
        'sale_price', 'wholesale_price', 'min_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /**
     * الأسماء البديلة اللي اتعلّمها النظام من الفواتير الممسوحة.
     *
     * @return HasMany<ProductAlias, $this>
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class)->latest();
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /** الرصيد الحالي = مجموع الحركات (في مخزن معين أو كل المخازن) */
    public function stock(?int $warehouseId = null): float
    {
        return (float) $this->movements()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->sum('quantity');
    }

    /** السعر حسب شريحة التاجر */
    public function priceFor(Customer $customer): float
    {
        if ($customer->price_tier === 'wholesale' && $this->wholesale_price) {
            return (float) $this->wholesale_price;
        }

        return (float) $this->sale_price;
    }
}
