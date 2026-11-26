<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ورقة جرد.
 *
 * بتتفتح على مخزن (وتصنيف اختياري)، وبتتجمّد عليها أرصدة النظام وقت
 * الفتح. المخزن بيعدّ ويكتب المعدود، والاعتماد بيولّد أذون الإضافة
 * والخصم للفروق لوحده.
 */
class StockCount extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'stock_count';

    /**
     * الحقول اللي بيتسجّل تغيّرها
     *
     * @var array<int, string>
     */
    protected $activityFields = ['number', 'warehouse_id', 'category_id', 'date', 'status', 'notes'];

    public const STATUS_OPEN = 'open';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'مفتوح',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    protected $fillable = [
        'number', 'warehouse_id', 'category_id', 'user_id', 'approved_by',
        'date', 'status', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<StockCountItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    /** الأذون اللي الاعتماد ولّدها */
    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
