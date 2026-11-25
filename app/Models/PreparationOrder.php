<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * طلب تحضير — ورقة بتروح للمخزن يجهّز عليها بضاعة تاجر.
 *
 * مفيش أسعار هنا خالص، لا في الجدول ولا في الورقة: الورقة دي بتروح
 * لعامل المخزن، ومحدش في المخزن محتاج يعرف التاجر بيشتري بكام.
 *
 * الطلب المفتوح أو المحضّر **بيحجز** الكميات: المتاح في كل النظام
 * (منتقي الأصناف، شاشة المخزون، فاتورة البيع) = الرصيد ناقص المحجوز.
 * الإلغاء أو التحويل لفاتورة بيفكّ الحجز.
 */
class PreparationOrder extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'preparation_order';

    /**
     * الحقول اللي بيتسجّل تغيّرها — مش كل الأعمدة
     *
     * @var array<int, string>
     */
    protected $activityFields = ['number', 'status', 'customer_id', 'warehouse_id', 'date', 'invoice_id', 'notes'];

    /** اتفتح لسه — الكميات محجوزة */
    public const STATUS_OPEN = 'open';

    /** المخزن جهّزه — الكميات لسه محجوزة */
    public const STATUS_PREPARED = 'prepared';

    /** اتحوّل لفاتورة بيع — الحجز اتفكّ والبضاعة اتخصمت فعلاً */
    public const STATUS_CONVERTED = 'converted';

    /** اتلغى — الحجز اتفكّ من غير أي حركة مخزون */
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * الحالات اللي بتحجز كميات.
     *
     * المصدر الوحيد للحقيقة دي — `InventoryService` بيقرا منها، فأي
     * حالة جديدة بتتزوّد هنا بتسري على حساب المتاح في النظام كله.
     */
    public const RESERVING = [self::STATUS_OPEN, self::STATUS_PREPARED];

    /** الأسماء العربية للحالات */
    public const STATUS_LABELS = [
        self::STATUS_OPEN => 'مفتوح',
        self::STATUS_PREPARED => 'تم التحضير',
        self::STATUS_CONVERTED => 'تم التحويل لفاتورة',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    protected $fillable = [
        'number', 'customer_id', 'warehouse_id', 'user_id', 'date',
        'status', 'invoice_id', 'prepared_at', 'cancelled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'prepared_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return HasMany<PreparationOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PreparationOrderItem::class);
    }

    /** الطلبات اللي لسه حاجزة كميات */
    public function scopeReserving(Builder $query): void
    {
        $query->whereIn('status', self::RESERVING);
    }

    /** ينفع يتعدّل؟ المحوّل والملغي اتقفلوا */
    public function isEditable(): bool
    {
        return in_array($this->status, self::RESERVING, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
