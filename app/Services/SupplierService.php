<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\Concerns\PreventsDeletingUsedRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * الموردين: القائمة، المستحق، وكشف الحساب.
 *
 * كل أرقام المورد بعملته هو (USD / EUR / TRY) عشان الرصيد يفضل متّسق.
 * التحويل للجنيه بيحصل وقت العرض المجمّع بس (الخزينة والتقارير).
 */
class SupplierService
{
    use PreventsDeletingUsedRecords;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listWithBalances(): Collection
    {
        return Supplier::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'country' => $supplier->country,
                'phone' => $supplier->phone,
                'currency' => $supplier->currency,
                'openingBalance' => (float) $supplier->opening_balance,
                'notes' => $supplier->notes,
                'isActive' => (bool) $supplier->is_active,
                ...$this->summary($supplier),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Supplier $supplier): array
    {
        $confirmed = $supplier->invoices()->where('status', Invoice::STATUS_CONFIRMED);

        $purchases = (float) (clone $confirmed)->where('type', Invoice::TYPE_PURCHASE)->sum('total');
        $returns = (float) (clone $confirmed)->where('type', Invoice::TYPE_PURCHASE_RETURN)->sum('total');
        $paid = (float) $supplier->payments()->sum('amount');

        // الإجمالي بالجنيه للتقارير المجمّعة
        $purchasesEgp = (float) (clone $confirmed)
            ->where('type', Invoice::TYPE_PURCHASE)
            ->sum(DB::raw('total * exchange_rate'));

        return [
            'purchases' => $purchases,
            'returns' => $returns,
            'paid' => $paid,
            // مقرّبة لخانتين زي الكشف بالظبط عشان المقارنة بينهم ما تبقاش عرضة لفروق الفاصلة العائمة
            'balance' => round((float) $supplier->opening_balance + $purchases - $returns - $paid, 2),
            'purchasesEgp' => $purchasesEgp,
            'invoiceCount' => (clone $confirmed)->where('type', Invoice::TYPE_PURCHASE)->count(),
        ];
    }

    /**
     * كشف حساب المورد بعملته.
     *
     * @return array{opening: float, rows: list<array<string, mixed>>, closing: float}
     */
    public function statement(Supplier $supplier): array
    {
        $invoices = $supplier->invoices()
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->whereIn('type', [Invoice::TYPE_PURCHASE, Invoice::TYPE_PURCHASE_RETURN])
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'kind' => $invoice->type,
                'date' => $invoice->date->toDateString(),
                'reference' => $invoice->number,
                'description' => $invoice->type === Invoice::TYPE_PURCHASE
                    ? 'فاتورة شراء'
                    : 'مرتجع مشتريات',
                // دائن = بيزوّد المستحق للمورد، مدين = بيقلّله
                'credit' => $invoice->type === Invoice::TYPE_PURCHASE ? (float) $invoice->total : 0.0,
                'debit' => $invoice->type === Invoice::TYPE_PURCHASE_RETURN ? (float) $invoice->total : 0.0,
                'link' => "/purchases/{$invoice->id}",
            ]);

        $payments = $supplier->payments()
            ->get()
            ->map(fn ($payment) => [
                'id' => 1_000_000 + $payment->id,
                'kind' => 'payment',
                'date' => $payment->date->toDateString(),
                'reference' => $payment->reference ?? '—',
                'description' => 'دفعة — '.(PaymentService::METHOD_LABELS[$payment->method] ?? $payment->method),
                'credit' => 0.0,
                'debit' => (float) $payment->amount,
                'link' => null,
            ]);

        $rows = $invoices->concat($payments)
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->values();

        $balance = (float) $supplier->opening_balance;
        $output = [];

        foreach ($rows as $row) {
            $balance += $row['credit'] - $row['debit'];
            $output[] = [...$row, 'balance' => round($balance, 2)];
        }

        return [
            'opening' => round((float) $supplier->opening_balance, 2),
            'rows' => $output,
            'closing' => round($balance, 2),
        ];
    }

    /** إجمالي المستحق لكل الموردين بالجنيه */
    public function totalPayableEgp(): float
    {
        $total = 0.0;

        foreach (Supplier::all() as $supplier) {
            $summary = $this->summary($supplier);
            $rate = (float) ($supplier->invoices()
                ->where('type', Invoice::TYPE_PURCHASE)
                ->latest('date')
                ->value('exchange_rate') ?? 1);

            $total += $summary['balance'] * $rate;
        }

        return round($total, 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        return DB::transaction(fn () => Supplier::create($this->attributes($data)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update($this->attributes($data));

            return $supplier;
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(Supplier $supplier): void
    {
        $this->guardAgainstDeletion('supplier', $supplier->name, [
            'فاتورة' => $supplier->invoices()->count(),
            'سند صرف' => $supplier->payments()->count(),
        ]);

        DB::transaction(fn () => $supplier->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'country' => $data['country'] ?? null,
            'currency' => $data['currency'],
            'phone' => $data['phone'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }
}
