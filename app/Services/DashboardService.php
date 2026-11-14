<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * كل استعلامات لوحة التحكم. مفيش أي استعلام في الـ controller.
 *
 * ملاحظة مهمة: المبيعات كلها بالجنيه (exchange_rate = 1)، فالتجميع عليها مباشر.
 * المشتريات بعملات مختلفة — فلو اتضافت للوحة لازم تتضرب في exchange_rate.
 */
class DashboardService
{
    /** مدة الرسم البياني */
    private const CHART_DAYS = 90;

    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * حالة تجهيز النظام — بتغذّي الشاشة الفاضية بدل ما نعرض أصفار ورسم فاضي.
     *
     * hasData = في حركة فعلية (فاتورة بيع أو شراء). الخطوات بترتيب التشغيل
     * الطبيعي: أصناف وموردين وتجار، بعدين شراء يدخّل بضاعة، بعدين بيع.
     *
     * @return array<string, mixed>
     */
    public function setup(): array
    {
        $counts = [
            'products' => Product::count(),
            'suppliers' => Supplier::count(),
            'customers' => Customer::count(),
            'purchases' => Invoice::query()->purchases()->count(),
            'sales' => Invoice::query()->sales()->count(),
        ];

        $steps = [
            ['key' => 'products', 'label' => 'سجّل أصنافك', 'href' => '/inventory', 'cta' => 'شاشة المخزون'],
            ['key' => 'suppliers', 'label' => 'سجّل موردينك', 'href' => '/suppliers', 'cta' => 'شاشة الموردين'],
            ['key' => 'customers', 'label' => 'سجّل تجارك', 'href' => '/customers', 'cta' => 'شاشة التجار'],
            ['key' => 'purchases', 'label' => 'أول فاتورة شراء تدخّل البضاعة المخزن', 'href' => '/purchases/create', 'cta' => 'فاتورة شراء جديدة'],
            ['key' => 'sales', 'label' => 'أول فاتورة بيع لتاجر', 'href' => '/sales/create', 'cta' => 'فاتورة بيع جديدة'],
        ];

        return [
            'hasData' => $counts['purchases'] > 0 || $counts['sales'] > 0,
            'steps' => array_map(
                fn (array $step) => $step + ['done' => $counts[$step['key']] > 0],
                $steps,
            ),
        ];
    }

    /**
     * كروت المؤشرات. مقارنة الشهر بتتعمل على نفس عدد الأيام من الشهر اللي فات
     * (١–١٣ نوفمبر مقابل ١–١٣ أكتوبر) عشان المقارنة تبقى عادلة مش شهر كامل مقابل ناقص.
     *
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $previousStart = $monthStart->copy()->subMonthNoOverflow();
        $previousEnd = $previousStart->copy()->addDays($today->day - 1);

        $current = $this->salesBetween($monthStart, $today);
        $previous = $this->salesBetween($previousStart, min($previousEnd, $previousStart->copy()->endOfMonth()));

        return [
            'sales' => [
                'value' => $current,
                'previous' => $previous,
                'change' => $this->percentChange($previous, $current),
                'periodDays' => $today->day,
            ],
            'receivables' => [
                'value' => $this->receivables(),
                'traders' => Customer::query()->count(),
            ],
            'inventoryValue' => ['value' => $this->inventory->inventoryValue()],
            'lowStock' => ['value' => $this->inventory->lowStockCount()],
        ];
    }

    /**
     * مبيعات آخر ٣ شهور — يومي وأسبوعي مع بعض عشان التبديل يبقى فوري من غير طلب جديد.
     * الأسبوع بيبدأ السبت (التقويم المصري).
     *
     * @return array{daily: list<array<string, mixed>>, weekly: list<array<string, mixed>>}
     */
    public function salesSeries(): array
    {
        $from = Carbon::today()->subDays(self::CHART_DAYS - 1);
        $to = Carbon::today();

        $rows = DB::table('invoices')
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->tap(fn ($query) => $this->whereDateBetween($query, $from, $to))
            ->groupBy('date')
            ->selectRaw('date, sum(total) as total, count(*) as invoices')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $daily = [];
        $weekly = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);

            $daily[] = [
                'date' => $key,
                'total' => (float) ($row->total ?? 0),
                'invoices' => (int) ($row->invoices ?? 0),
            ];

            // تجميع أسبوعي بمفتاح أول يوم في الأسبوع
            $weekStart = $date->copy()->startOfWeek(Carbon::SATURDAY)->toDateString();

            if (! isset($weekly[$weekStart])) {
                $weekly[$weekStart] = [
                    'date' => $weekStart,
                    'endDate' => $date->copy()->startOfWeek(Carbon::SATURDAY)->addDays(6)->toDateString(),
                    'total' => 0.0,
                    'invoices' => 0,
                ];
            }

            $weekly[$weekStart]['total'] += (float) ($row->total ?? 0);
            $weekly[$weekStart]['invoices'] += (int) ($row->invoices ?? 0);
        }

        return ['daily' => $daily, 'weekly' => array_values($weekly)];
    }

    /**
     * أعلى ٥ أصناف مبيعًا خلال فترة الرسم، مرتبة بقيمة المبيعات.
     *
     * @return Collection<int, object>
     */
    public function topProducts(int $limit = 5): Collection
    {
        $from = Carbon::today()->subDays(self::CHART_DAYS - 1)->toDateString();

        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->where('invoices.date', '>=', $from)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit', 'categories.name')
            ->selectRaw('products.id, products.sku, products.name, products.unit, categories.name as category, sum(invoice_items.quantity) as quantity, sum(invoice_items.total) as revenue')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * أعلى ٥ تجار مديونية.
     *
     * @return Collection<int, Customer>
     */
    public function topDebtors(int $limit = 5): Collection
    {
        return Customer::query()
            ->select(['id', 'name', 'city', 'phone', 'credit_limit', 'opening_balance'])
            ->selectRaw($this->balanceSql().' as balance')
            ->orderByDesc('balance')
            ->limit($limit)
            ->get();
    }

    /**
     * آخر الفواتير بكل أنواعها.
     *
     * @return Collection<int, Invoice>
     */
    public function latestInvoices(int $limit = 8): Collection
    {
        return Invoice::query()
            ->with(['customer:id,name', 'supplier:id,name'])
            ->latest('date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** إجمالي مديونيات التجار = افتتاحي + مبيعات - مرتجعات - تحصيلات */
    private function receivables(): float
    {
        return (float) Customer::query()
            ->selectRaw('coalesce(sum('.$this->balanceSql().'), 0) as total')
            ->value('total');
    }

    private function salesBetween(Carbon $from, Carbon $to): float
    {
        return (float) DB::table('invoices')
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->tap(fn ($query) => $this->whereDateBetween($query, $from, $to))
            ->sum('total');
    }

    /**
     * مقارنة تواريخ شاملة لليوم الأخير وشغّالة على MySQL و SQLite مع بعض.
     *
     * السبب: الـ cast بتاع 'date' بيكتب في القاعدة بصيغة Y-m-d H:i:s. عمود DATE
     * في MySQL بيقصّ الوقت، لكن SQLite بيخزّن النص كامل — فمقارنة
     * between ... and '2026-11-13' كانت بتسقط فواتير النهارده. الحل: الحد
     * الأعلى يبقى "أقل من بكرة" بدل "أصغر من أو يساوي النهارده".
     *
     * @param  Builder  $query
     */
    private function whereDateBetween($query, Carbon $from, Carbon $to): void
    {
        $query->where('date', '>=', $from->toDateString())
            ->where('date', '<', $to->copy()->addDay()->toDateString());
    }

    /** نسبة التغيّر؛ null لو مفيش أساس نقارن عليه */
    private function percentChange(float $previous, float $current): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** رصيد التاجر كـ SQL — شغّال على MySQL و SQLite */
    private function balanceSql(): string
    {
        $sale = Invoice::TYPE_SALE;
        $saleReturn = Invoice::TYPE_SALE_RETURN;
        $confirmed = Invoice::STATUS_CONFIRMED;

        return "(customers.opening_balance
            + (select coalesce(sum(total), 0) from invoices where invoices.customer_id = customers.id and invoices.type = '{$sale}' and invoices.status = '{$confirmed}')
            - (select coalesce(sum(total), 0) from invoices where invoices.customer_id = customers.id and invoices.type = '{$saleReturn}' and invoices.status = '{$confirmed}')
            - (select coalesce(sum(amount), 0) from payments where payments.customer_id = customers.id))";
    }
}
