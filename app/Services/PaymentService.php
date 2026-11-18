<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * تسجيل التحصيلات من التجار والمدفوعات للموردين.
 *
 * كل عملية جوه transaction عشان لو ترحيل المبلغ على الفاتورة فشل
 * ما يفضلش سند مسجّل من غير ترحيل.
 */
class PaymentService
{
    public const METHODS = ['cash', 'bank', 'check'];

    public const METHOD_LABELS = [
        'cash' => 'نقدي',
        'bank' => 'تحويل بنكي',
        'check' => 'شيك',
    ];

    /**
     * تحصيل من تاجر.
     *
     * @param  array{amount: float, method: string, date: string, reference?: ?string, notes?: ?string, invoice_id?: ?int}  $data
     */
    public function recordReceipt(Customer $customer, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($customer, $data, $user) {
            $invoice = $this->resolveInvoice($data['invoice_id'] ?? null, $customer);

            $payment = Payment::create([
                'type' => Payment::TYPE_RECEIPT,
                'customer_id' => $customer->id,
                'supplier_id' => null,
                'invoice_id' => $invoice?->id,
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'],
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'user_id' => $user->id,
                // التحصيل بيتنسب لمندوب التاجر — العمولة بتتحسب على المحصّل،
                // فمن غير النسبة دي المندوب عمره ما هياخد عمولة
                'sales_rep_id' => $customer->sales_rep_id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($invoice) {
                $this->refreshInvoicePaid($invoice);
            }

            return $payment;
        });
    }

    /**
     * دفع لمورد — بعملة المورد نفسها عشان رصيده يفضل متّسق.
     *
     * @param  array{amount: float, method: string, date: string, reference?: ?string, notes?: ?string, invoice_id?: ?int, currency?: ?string, exchange_rate?: ?float}  $data
     */
    public function recordDisbursement(Supplier $supplier, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($supplier, $data, $user) {
            $invoice = $this->resolveInvoice($data['invoice_id'] ?? null, null, $supplier);

            $payment = Payment::create([
                'type' => Payment::TYPE_DISBURSEMENT,
                'customer_id' => null,
                'supplier_id' => $supplier->id,
                'invoice_id' => $invoice?->id,
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'],
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
                // الدفع بعملة المورد، وسعر الصرف بيتخزّن عشان نقدر نحوّل للجنيه بعدين
                'currency' => $data['currency'] ?? $invoice?->currency ?? $supplier->currency,
                'exchange_rate' => $data['exchange_rate'] ?? $invoice?->exchange_rate ?? 1,
            ]);

            if ($invoice) {
                $this->refreshInvoicePaid($invoice);
            }

            return $payment;
        });
    }

    /** إعادة حساب المدفوع على الفاتورة من واقع سنداتها */
    public function refreshInvoicePaid(Invoice $invoice): void
    {
        $invoice->forceFill([
            'paid' => (float) $invoice->payments()->sum('amount'),
        ])->save();
    }

    /** الفاتورة لازم تكون بتاعة نفس الطرف — مش أي فاتورة في النظام */
    private function resolveInvoice(?int $invoiceId, ?Customer $customer = null, ?Supplier $supplier = null): ?Invoice
    {
        if (! $invoiceId) {
            return null;
        }

        $invoice = Invoice::find($invoiceId);

        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice_id' => 'الفاتورة المختارة مش موجودة.',
            ]);
        }

        if ($customer && $invoice->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'invoice_id' => 'الفاتورة المختارة مش تابعة لنفس التاجر.',
            ]);
        }

        if ($supplier && $invoice->supplier_id !== $supplier->id) {
            throw ValidationException::withMessages([
                'invoice_id' => 'الفاتورة المختارة مش تابعة لنفس المورد.',
            ]);
        }

        return $invoice;
    }
}
