<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * قوائم فواتير البيع والفلاتر بتاعتها.
 */
class SalesQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::query()
            ->whereIn('type', [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN])
            ->with(['customer:id,name', 'warehouse:id,name'])
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
                })
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn (Builder $query) => $query->where('customer_id', $filters['customer_id'])
            )
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id'])
            )
            ->when(
                filled($filters['eta'] ?? null),
                fn (Builder $query) => $filters['eta'] === 'none'
                    ? $query->whereNull('eta_status')
                    : $query->where('eta_status', $filters['eta'])
            )
            ->when(
                ($filters['payment'] ?? null) === 'unpaid',
                fn (Builder $query) => $query->whereColumn('paid', '<', 'total')
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                ($filters['payment'] ?? null) === 'paid',
                fn (Builder $query) => $query->whereColumn('paid', '>=', 'total')
            )
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $query) => $query->where('date', '>=', Carbon::parse($filters['from'])->toDateString())
            )
            ->when(
                filled($filters['to'] ?? null),
                // "أقل من بكرة" عشان اليوم الأخير يدخل على MySQL و SQLite
                fn (Builder $query) => $query->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString())
            )
            ->latest('date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * إجماليات الفترة المفلترة — بتتحسب على كل النتائج مش الصفحة الحالية بس.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function totals(array $filters): array
    {
        $base = $this->paginate($filters, 1)->total();

        $query = Invoice::query()
            ->where('type', Invoice::TYPE_SALE)
            // المسودات لسه ما اتأكّدتش، فما بتدخلش في أي إجمالي
            ->confirmed()
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $q) => $q->where('date', '>=', Carbon::parse($filters['from'])->toDateString())
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $q) => $q->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString())
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn (Builder $q) => $q->where('customer_id', $filters['customer_id'])
            );

        return [
            'count' => $base,
            'total' => (float) (clone $query)->sum('total'),
            'paid' => (float) (clone $query)->sum('paid'),
            'outstanding' => (float) (clone $query)->sum('total') - (float) (clone $query)->sum('paid'),
        ];
    }
}
