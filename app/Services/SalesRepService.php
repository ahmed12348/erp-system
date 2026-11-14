<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesRep;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * أداء مندوبي المبيعات.
 *
 * العمولة بتتحسب على **المحصّل** مش على المبيعات — ده الشائع في التوزيع
 * في مصر، لأن المندوب مسؤول عن التحصيل مش بس البيع، والفاتورة اللي
 * مااتحصّلتش مابتستحقش عمولة.
 */
class SalesRepService
{
    /**
     * @param  array{from?: ?string, to?: ?string}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function performance(array $filters = []): Collection
    {
        return SalesRep::query()
            ->orderBy('name')
            ->get()
            ->map(fn (SalesRep $rep) => $this->summary($rep, $filters));
    }

    /**
     * @param  array{from?: ?string, to?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function summary(SalesRep $rep, array $filters = []): array
    {
        $sales = $this->scope($rep->invoices(), $filters)
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED);

        $returns = $this->scope($rep->invoices(), $filters)
            ->where('type', Invoice::TYPE_SALE_RETURN)
            ->where('status', Invoice::STATUS_CONFIRMED);

        $collected = (float) $this->scope($rep->payments(), $filters)
            ->where('type', Payment::TYPE_RECEIPT)
            ->sum('amount');

        $salesTotal = (float) (clone $sales)->sum('total');
        $returnsTotal = (float) $returns->sum('total');
        $netSales = round($salesTotal - $returnsTotal, 2);
        $rate = (float) $rep->commission_rate;

        return [
            'id' => $rep->id,
            'name' => $rep->name,
            'phone' => $rep->phone,
            'region' => $rep->region,
            'commissionRate' => $rate,
            'isActive' => (bool) $rep->is_active,
            'customers' => $rep->customers()->count(),
            'invoices' => (clone $sales)->count(),
            'sales' => round($salesTotal, 2),
            'returns' => round($returnsTotal, 2),
            'netSales' => $netSales,
            'collected' => round($collected, 2),
            // المستحق على تجار المندوب كلهم (مش مقيّد بالفترة — ده رصيد قائم)
            'outstanding' => $this->outstandingFor($rep),
            'commission' => round($collected * $rate / 100, 2),
            'collectionRate' => $netSales > 0 ? round($collected / $netSales * 100, 1) : null,
        ];
    }

    /** إجمالي أرصدة التجار التابعين للمندوب */
    private function outstandingFor(SalesRep $rep): float
    {
        $sale = Invoice::TYPE_SALE;
        $saleReturn = Invoice::TYPE_SALE_RETURN;
        $confirmed = Invoice::STATUS_CONFIRMED;

        return round((float) DB::table('customers')
            ->where('sales_rep_id', $rep->id)
            ->selectRaw("coalesce(sum(
                customers.opening_balance
                + (select coalesce(sum(total), 0) from invoices where invoices.customer_id = customers.id and invoices.type = '{$sale}' and invoices.status = '{$confirmed}')
                - (select coalesce(sum(total), 0) from invoices where invoices.customer_id = customers.id and invoices.type = '{$saleReturn}' and invoices.status = '{$confirmed}')
                - (select coalesce(sum(amount), 0) from payments where payments.customer_id = customers.id)
            ), 0) as total")
            ->value('total'), 2);
    }

    /**
     * فلتر الفترة. بياخد Relation أو Builder ويرجّعه زي ما هو — الاتنين
     * بيدعموا when()/where()، فما ينفعش نضيّق النوع على Builder لوحده.
     *
     * @param  array{from?: ?string, to?: ?string}  $filters
     */
    private function scope(mixed $query, array $filters): mixed
    {
        return $query
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $q) => $q->where('date', '>=', Carbon::parse($filters['from'])->toDateString())
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $q) => $q->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString())
            );
    }
}
