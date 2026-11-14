<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE_RETURN = 'sale_return';

    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    /** حالات منظومة الفاتورة الإلكترونية (ETA) — null يعني الفاتورة لسه مااتبعتتش */
    public const ETA_PENDING = 'pending';

    public const ETA_SENT = 'sent';

    public const ETA_SUBMITTED = 'submitted';

    public const ETA_VALID = 'valid';

    public const ETA_REJECTED = 'rejected';

    protected $fillable = [
        'type', 'number', 'customer_id', 'supplier_id', 'warehouse_id', 'user_id', 'sales_rep_id',
        'date', 'due_date', 'currency', 'exchange_rate', 'subtotal', 'discount',
        'tax', 'shipping_cost', 'customs_cost', 'clearance_cost',
        'total', 'paid', 'status', 'eta_status', 'eta_uuid', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'customs_cost' => 'decimal:2',
            'clearance_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
        ];
    }

    protected $appends = ['remaining'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function getRemainingAttribute(): float
    {
        return (float) $this->total - (float) $this->paid;
    }

    public function scopeSales($query)
    {
        return $query->where('type', self::TYPE_SALE);
    }

    public function scopePurchases($query)
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }
}
