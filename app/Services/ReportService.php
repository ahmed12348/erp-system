<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تقارير المبيعات والمخزون.
 *
 * كل الفواتير اللي بتدخل التقارير مؤكّدة (confirmed) بس — المسودّات
 * والملغي مابيدخلوش أي رقم.
 */
class ReportService
{
    /**
     * مبيعات على فترة، مجمّعة باليوم أو الشهر.
     *
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function salesByPeriod(string $from, string $to, string $groupBy = 'day'): array
    {
        $invoices = Invoice::query()
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->where('date', '>=', Carbon::parse($from)->toDateString())
            ->where('date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->get(['date', 'subtotal', 'tax', 'total', 'paid']);

        $buckets = [];

        foreach ($invoices as $invoice) {
            // التجميع في PHP عشان صيغ التاريخ في MySQL و SQLite مختلفة
            $key = $groupBy === 'month'
                ? $invoice->date->format('Y-m')
                : $invoice->date->toDateString();

            $buckets[$key] ??= ['period' => $key, 'count' => 0, 'net' => 0.0, 'tax' => 0.0, 'total' => 0.0, 'paid' => 0.0];

            $buckets[$key]['count']++;
            $buckets[$key]['net'] += (float) $invoice->subtotal;
            $buckets[$key]['tax'] += (float) $invoice->tax;
            $buckets[$key]['total'] += (float) $invoice->total;
            $buckets[$key]['paid'] += (float) $invoice->paid;
        }

        krsort($buckets);
        $rows = array_values($buckets);

        return [
            'rows' => $rows,
            'totals' => [
                'count' => (float) array_sum(array_column($rows, 'count')),
                'net' => round(array_sum(array_column($rows, 'net')), 2),
                'tax' => round(array_sum(array_column($rows, 'tax')), 2),
                'total' => round(array_sum(array_column($rows, 'total')), 2),
                'paid' => round(array_sum(array_column($rows, 'paid')), 2),
            ],
        ];
    }

    /**
     * مبيعات وأرباح كل صنف — الربح = قيمة البيع ناقص (الكمية × تكلفة الصنف).
     *
     * @return Collection<int, object>
     */
    public function salesByProduct(string $from, string $to, int $limit = 100): Collection
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->where('invoices.date', '>=', Carbon::parse($from)->toDateString())
            ->where('invoices.date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit', 'products.purchase_price', 'categories.name')
            ->selectRaw('products.id, products.sku, products.name, products.unit, categories.name as category,
                sum(invoice_items.quantity) as quantity,
                sum(invoice_items.total) as revenue,
                sum(invoice_items.quantity * products.purchase_price) as cost')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->profit = round((float) $row->revenue - (float) $row->cost, 2);
                $row->margin = (float) $row->revenue > 0
                    ? round($row->profit / (float) $row->revenue * 100, 1)
                    : 0.0;

                return $row;
            });
    }

    /**
     * مبيعات كل تاجر في الفترة + رصيده الحالي.
     *
     * @return Collection<int, object>
     */
    public function salesByCustomer(string $from, string $to): Collection
    {
        return DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->where('invoices.date', '>=', Carbon::parse($from)->toDateString())
            ->where('invoices.date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->groupBy('customers.id', 'customers.name', 'customers.city')
            ->selectRaw('customers.id, customers.name, customers.city,
                count(*) as invoices,
                sum(invoices.total) as total,
                sum(invoices.paid) as paid')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->outstanding = round((float) $row->total - (float) $row->paid, 2);

                return $row;
            });
    }

    /**
     * تقييم المخزون بالتكلفة وبسعر البيع.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function inventoryValuation(): array
    {
        $rows = app(InventoryService::class)->stockRows()
            ->map(fn (array $row) => [
                ...$row,
                'retailValue' => round($row['total'] * $row['salePrice'], 2),
            ])
            ->filter(fn (array $row) => $row['total'] != 0.0)
            ->sortByDesc('value')
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'units' => round((float) $rows->sum('total'), 3),
                'cost' => round((float) $rows->sum('value'), 2),
                'retail' => round((float) $rows->sum('retailValue'), 2),
                'items' => $rows->count(),
            ],
        ];
    }
}
