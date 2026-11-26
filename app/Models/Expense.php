<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * مصروف — بيطلع من الخزينة.
 *
 * المصروف بيعمل سند في `payments` بنوع `expense`، عشان الخزينة تفضل
 * مصدر واحد للفلوس بدل جدولين بيتجمعوا على الشاشة.
 */
class Expense extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'expense';

    /**
     * الحقول اللي بيتسجّل تغيّرها
     *
     * @var array<int, string>
     */
    protected $activityFields = ['number', 'expense_category_id', 'date', 'amount', 'method', 'paid_to', 'notes'];

    protected $fillable = [
        'number', 'expense_category_id', 'user_id', 'payment_id',
        'date', 'amount', 'method', 'paid_to', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ExpenseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
