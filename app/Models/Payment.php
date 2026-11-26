<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    use RecordsActivity;

    /** اسم السجل في سجل النشاط */
    protected $activityLog = 'payment';

    /** الحقول اللي بيتسجّل تغيّرها — مش كل الأعمدة */
    protected $activityFields = ['type', 'amount', 'currency', 'method', 'date', 'reference', 'customer_id', 'supplier_id', 'invoice_id', 'notes'];

    public const TYPE_RECEIPT = 'receipt';           // تحصيل من تاجر

    public const TYPE_DISBURSEMENT = 'disbursement'; // دفع لمورد

    /**
     * مصروف — بيطلع من الخزينة من غير طرف.
     *
     * نوع تالت مقصود إنه يفضل مميّز: عمولة المندوب بتتحسب على المحصّل
     * ناقص المردود (`disbursement`)، والمصروف مالوش علاقة بيها.
     */
    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'type', 'customer_id', 'supplier_id', 'invoice_id', 'expense_id', 'amount', 'currency',
        'exchange_rate', 'method', 'date', 'reference', 'user_id', 'sales_rep_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
