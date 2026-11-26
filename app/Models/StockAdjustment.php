<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * إذن إضافة أو خصم مخزني.
 *
 * بيتعمل مسودة، وبيتعمد من المدير أو المحاسب — والاعتماد هو اللي
 * بيعمل حركات المخزون. قبل الاعتماد الإذن ورقة مالهاش أي أثر على
 * الأرصدة، وده المقصود: الورقة بتتكتب في المخزن وبتتراجع قبل ما
 * تتحرّك بضاعة.
 */
class StockAdjustment extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'stock_adjustment';

    /**
     * الحقول اللي بيتسجّل تغيّرها
     *
     * @var array<int, string>
     */
    protected $activityFields = ['number', 'type', 'warehouse_id', 'date', 'reason', 'status', 'notes'];

    /** إذن إضافة — بضاعة داخلة */
    public const TYPE_ADDITION = 'addition';

    /** إذن خصم — بضاعة خارجة */
    public const TYPE_DEDUCTION = 'deduction';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const TYPE_LABELS = [
        self::TYPE_ADDITION => 'إذن إضافة',
        self::TYPE_DEDUCTION => 'إذن خصم',
    ];

    /**
     * أسباب الإضافة والخصم.
     *
     * نفس القايمة للنوعين — «جرد» بيتولّد من ورقة الجرد، والباقي
     * بيتكتب بالإيد.
     */
    public const REASONS = [
        'count' => 'جرد',
        'damaged' => 'تالف',
        'lost' => 'فقد',
        'gift' => 'هدية',
        'opening' => 'رصيد أول المدة',
        'other' => 'أخرى',
    ];

    protected $fillable = [
        'number', 'type', 'warehouse_id', 'user_id', 'approved_by',
        'stock_count_id', 'date', 'reason', 'status', 'approved_at', 'notes',
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

    /** @return BelongsTo<StockCount, $this> */
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    /** @return HasMany<StockAdjustmentItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    /** حركات المخزون اللي الإذن عملها وقت الاعتماد */
    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isAddition(): bool
    {
        return $this->type === self::TYPE_ADDITION;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /** قيمة الإذن بالتكلفة */
    public function value(): float
    {
        return round((float) $this->items->sum(
            fn (StockAdjustmentItem $item) => (float) $item->quantity * (float) $item->unit_cost
        ), 2);
    }
}
