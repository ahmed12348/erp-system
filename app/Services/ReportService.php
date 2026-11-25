<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * تقارير المبيعات والمخزون.
 *
 * كل الفواتير اللي بتدخل التقارير مؤكّدة (confirmed) بس — المسودّات
 * والملغي مابيدخلوش أي رقم.
 *
 * **البحث برقم الفاتورة** بيعدّي على كل تقرير: بيضيّق التقرير على
 * الفاتورة (أو الفواتير) اللي رقمها فيه النص المدوّر عليه. مطابقة جزئية،
 * فـ «0001» بتجيب `S-2026-00001` — محدش بيكتب الرقم كامل وهو بيدوّر.
 */
class ReportService
{
    /** حالات الفاتورة بالعربي — زي ما الشاشة بتعرضها */
    private const STATUS_LABELS = [
        Invoice::STATUS_DRAFT => 'مسودة',
        Invoice::STATUS_CONFIRMED => 'مؤكّدة',
        Invoice::STATUS_CANCELLED => 'ملغية',
    ];

    /**
     * مبيعات على فترة، مجمّعة باليوم أو الشهر.
     *
     * كل سطر شايل معاه فواتيره بأرقامها، عشان الشاشة تفتح اليوم وتوريها
     * من غير نداء تاني. يوم بـ 40 ألف جنيه سؤاله الطبيعي هو «ده كان
     * أنهي فواتير؟».
     *
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function salesByPeriod(string $from, string $to, string $groupBy = 'day', ?string $search = null): array
    {
        $invoices = Invoice::query()
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->where('date', '>=', Carbon::parse($from)->toDateString())
            ->where('date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->tap(fn ($query) => $this->applyNumberSearch($query, $search))
            ->orderBy('number')
            ->get(['id', 'number', 'date', 'subtotal', 'tax', 'total', 'paid']);

        $buckets = [];

        foreach ($invoices as $invoice) {
            // التجميع في PHP عشان صيغ التاريخ في MySQL و SQLite مختلفة
            $key = $groupBy === 'month'
                ? $invoice->date->format('Y-m')
                : $invoice->date->toDateString();

            $buckets[$key] ??= ['period' => $key, 'count' => 0, 'net' => 0.0, 'tax' => 0.0, 'total' => 0.0, 'paid' => 0.0, 'invoices' => []];

            $buckets[$key]['count']++;
            $buckets[$key]['net'] += (float) $invoice->subtotal;
            $buckets[$key]['tax'] += (float) $invoice->tax;
            $buckets[$key]['total'] += (float) $invoice->total;
            $buckets[$key]['paid'] += (float) $invoice->paid;

            $buckets[$key]['invoices'][] = [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid,
            ];
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
    public function salesByProduct(string $from, string $to, int $limit = 100, ?string $search = null): Collection
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->where('invoices.date', '>=', Carbon::parse($from)->toDateString())
            ->where('invoices.date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->tap(fn ($query) => $this->applyNumberSearch($query, $search, 'invoices.number'))
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
    public function salesByCustomer(string $from, string $to, ?string $search = null): Collection
    {
        return DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->where('invoices.date', '>=', Carbon::parse($from)->toDateString())
            ->where('invoices.date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->tap(fn ($query) => $this->applyNumberSearch($query, $search, 'invoices.number'))
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
     * فاتورة في كل سطر — مبيعات أو مشتريات.
     *
     * التقارير التانية بتجمّع (باليوم، بالصنف، بالتاجر)، وده بيورّي
     * الفواتير نفسها. السؤال اللي بيجاوب عليه مختلف: مش «بعنا بكام»
     * لكن «أنهي فواتير، ومين لسه مدين فيها».
     *
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>}
     */
    public function invoiceRows(string $type, string $from, string $to, ?string $search = null): array
    {
        $isPurchase = $type === Invoice::TYPE_PURCHASE;

        $invoices = Invoice::query()
            ->where('type', $type)
            ->with([
                'customer:id,name',
                'supplier:id,name',
                'salesRep:id,name',
            ])
            ->where('date', '>=', Carbon::parse($from)->toDateString())
            ->where('date', '<', Carbon::parse($to)->addDay()->toDateString())
            ->tap(fn ($query) => $this->applyNumberSearch($query, $search))
            ->latest('date')
            ->latest('id')
            ->get();

        $rows = $invoices->map(fn (Invoice $invoice): array => [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'date' => $invoice->date->toDateString(),
            'party' => $isPurchase
                ? ($invoice->supplier?->name ?? '—')
                : ($invoice->customer?->name ?? '—'),
            'rep' => $invoice->salesRep?->name,
            'net' => (float) $invoice->subtotal,
            'tax' => (float) $invoice->tax,
            'total' => (float) $invoice->total,
            'paid' => (float) $invoice->paid,
            'remaining' => $invoice->remaining,
            'status' => $invoice->status,
            'statusLabel' => self::STATUS_LABELS[$invoice->status] ?? $invoice->status,
            // رقم الفاتورة بيفتحها — ده أهم حاجة في التقرير ده
            'link' => ($isPurchase ? '/purchases/' : '/sales/').$invoice->id,
        ])->all();

        /*
         * الإجماليات بتعدّي على المؤكّد بس.
         *
         * الملغي بيبان في الجدول (المستخدم عايز يشوفه) لكن ما بيتجمعش
         * في السطر الأخير — وإلا الإجمالي ما بيطابقش أي تقرير تاني.
         */
        $confirmed = $invoices->where('status', Invoice::STATUS_CONFIRMED);

        return [
            'rows' => $rows,
            'totals' => [
                'count' => (float) $confirmed->count(),
                'net' => round((float) $confirmed->sum('subtotal'), 2),
                'tax' => round((float) $confirmed->sum('tax'), 2),
                'total' => round((float) $confirmed->sum('total'), 2),
                'paid' => round((float) $confirmed->sum('paid'), 2),
                'remaining' => round(
                    (float) $confirmed->sum('total') - (float) $confirmed->sum('paid'),
                    2,
                ),
            ],
        ];
    }

    /**
     * تقييم المخزون بالتكلفة وبسعر البيع.
     *
     * البحث برقم الفاتورة هنا معناه «ورّيني أرصدة أصناف الفاتورة دي» —
     * التقييم لقطة لحظية مالهاش فترة، فالفلترة الوحيدة اللي ليها معنى
     * هي على الأصناف اللي على الورقة.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function inventoryValuation(?string $search = null): array
    {
        $onInvoices = $this->productIdsOnInvoices($search);

        $rows = app(InventoryService::class)->stockRows()
            ->when(
                $onInvoices !== null,
                fn (Collection $rows) => $rows->filter(
                    fn (array $row) => in_array($row['id'], $onInvoices, true)
                )
            )
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

    /**
     * فلتر رقم الفاتورة — مطابقة جزئية.
     *
     * «0001» بتجيب `S-2026-00001`: محدش بيكتب الرقم كامل وهو بيدوّر،
     * بيكتب آخر كام رقم من الورقة اللي في إيده.
     *
     * @param  Builder  $query
     */
    private function applyNumberSearch($query, ?string $search, string $column = 'number'): void
    {
        if (blank($search)) {
            return;
        }

        $query->where($column, 'like', '%'.trim($search).'%');
    }

    /**
     * أكواد الأصناف اللي على الفواتير المطابقة، أو null لو مفيش بحث.
     *
     * `null` مش `[]` عن قصد: الفاضي معناه «الفاتورة دي مالهاش بنود»
     * والنتيجة لازم تبقى فاضية، أما `null` معناه «مفيش بحث أصلاً» والكل
     * بيعدّي.
     *
     * @return array<int, int>|null
     */
    private function productIdsOnInvoices(?string $search): ?array
    {
        if (blank($search)) {
            return null;
        }

        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.number', 'like', '%'.trim($search).'%')
            ->distinct()
            ->pluck('invoice_items.product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
