<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * الحسابات اللي بترد على أسئلة الوكلاء.
 *
 * القاعدة الذهبية: **كل رقم بيتحسب هنا في PHP/SQL**. الموديل بيشرح
 * ويوصّي بس — عمره ما بيجمع ولا بيقسم ولا بيقدّر. عشان كده كل أداة
 * بترجع أرقام جاهزة ومحسوبة، مش بيانات خام يستنتج منها.
 *
 * الفواتير المؤكّدة بس هي اللي بتتحسب — المسودات بره كل حاجة.
 */
class AnalyticsService
{
    /** رصيد الصنف = مجموع حركات المخزون */
    private const BALANCE = '(select coalesce(sum(sm.quantity), 0) from stock_movements sm where sm.product_id = products.id)';

    /**
     * أصناف راكدة: عندها رصيد وما اتباعتش من مدة.
     *
     * بترجع كمان قيمة الفلوس المحبوسة فيها — ده الرقم اللي بيخلّي
     * القرار واضح، مش عدد الأصناف.
     *
     * @return array{rows: array<int, array<string, mixed>>, tied: float, days: int}
     */
    public function slowMoving(int $days = 60, int $limit = 20): array
    {
        $since = Carbon::today()->subDays($days)->toDateString();

        $lastSale = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->groupBy('invoice_items.product_id')
            ->selectRaw('invoice_items.product_id, max(invoices.date) as last_sale');

        $rows = DB::table('products')
            ->leftJoinSub($lastSale, 'ls', 'ls.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->selectRaw('products.id, products.sku, products.name, products.unit, products.purchase_price')
            ->selectRaw(self::BALANCE.' as balance')
            ->selectRaw('ls.last_sale')
            // WHERE مش HAVING: SQLite بيرفض HAVING من غير تجميع
            ->whereRaw(self::BALANCE.' > 0')
            ->where(fn ($query) => $query->whereNull('ls.last_sale')->orWhere('ls.last_sale', '<', $since))
            ->orderByRaw('('.self::BALANCE.' * products.purchase_price) desc')
            ->limit($limit)
            ->get();

        $mapped = $rows->map(fn ($row): array => [
            'sku' => $row->sku,
            'name' => $row->name,
            'unit' => $row->unit,
            'balance' => (float) $row->balance,
            'value' => round((float) $row->balance * (float) $row->purchase_price, 2),
            'lastSale' => $row->last_sale,
            'idleDays' => $row->last_sale
                ? Carbon::parse($row->last_sale)->diffInDays(Carbon::today())
                : null,
        ])->all();

        return [
            'rows' => $mapped,
            'tied' => round(array_sum(array_column($mapped, 'value')), 2),
            'days' => $days,
        ];
    }

    /**
     * أصناف سريعة الحركة في فترة.
     *
     * بترتّب بالكمية أو بمعدّل الدوران. الدوران هنا = الكمية المباعة ÷
     * الرصيد الحالي: بيقول «الرصيد ده بيتقلب كام مرة في الفترة دي».
     *
     * @return array<int, array<string, mixed>>
     */
    public function fastMoving(string $from, string $to, string $by = 'units', int $limit = 15): array
    {
        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->whereBetween('invoices.date', [$from, $to])
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit')
            ->selectRaw('products.id, products.sku, products.name, products.unit')
            ->selectRaw('sum(invoice_items.quantity) as units')
            ->selectRaw('sum(invoice_items.total) as revenue')
            ->get();

        $mapped = $rows->map(function ($row): array {
            $balance = (float) DB::table('stock_movements')
                ->where('product_id', $row->id)
                ->sum('quantity');

            $units = (float) $row->units;

            return [
                'sku' => $row->sku,
                'name' => $row->name,
                'unit' => $row->unit,
                'units' => $units,
                'revenue' => round((float) $row->revenue, 2),
                'balance' => $balance,
                // الرصيد صفر يعني خلص خالص — دوران عالي مش معرّف
                'turnover' => $balance > 0 ? round($units / $balance, 2) : null,
            ];
        })->all();

        usort($mapped, fn (array $a, array $b): int => match ($by) {
            'revenue' => $b['revenue'] <=> $a['revenue'],
            'turnover' => ($b['turnover'] ?? 0) <=> ($a['turnover'] ?? 0),
            default => $b['units'] <=> $a['units'],
        });

        return array_slice($mapped, 0, $limit);
    }

    /**
     * احتياج الـ 30 يوم الجايين.
     *
     * المعدّل اليومي من آخر 90 يوم، مضروب في 30، ناقص الرصيد الحالي.
     * بنرجّع كمان طول التاريخ الفعلي عشان الأداة تقول للمستخدم صراحةً
     * لما البيانات أقل من شهر — توقّع مبني على أسبوع مش توقّع.
     *
     * @return array{rows: array<int, array<string, mixed>>, historyDays: int, reliable: bool}
     */
    public function forecastNeeds(int $horizonDays = 30, int $historyDays = 90, int $limit = 25): array
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays($historyDays);

        // أقدم فاتورة مؤكّدة — بيحدّد التاريخ المتاح فعلاً
        $earliest = Invoice::query()
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->min('date');

        $actualHistory = $earliest
            ? min($historyDays, (int) Carbon::parse($earliest)->diffInDays($to) + 1)
            : 0;

        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->whereBetween('invoices.date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit', 'products.min_stock')
            ->selectRaw('products.id, products.sku, products.name, products.unit, products.min_stock')
            ->selectRaw('sum(invoice_items.quantity) as units')
            ->get();

        // المقسوم عليه هو التاريخ المتاح فعلاً، مش 90 دايمًا
        $divisor = max($actualHistory, 1);

        $mapped = $rows->map(function ($row) use ($divisor, $horizonDays): array {
            $balance = (float) DB::table('stock_movements')
                ->where('product_id', $row->id)
                ->sum('quantity');

            $perDay = (float) $row->units / $divisor;
            $expected = $perDay * $horizonDays;
            $shortfall = $expected - $balance;

            return [
                'sku' => $row->sku,
                'name' => $row->name,
                'unit' => $row->unit,
                'perDay' => round($perDay, 2),
                'expected' => round($expected, 1),
                'balance' => $balance,
                // الناقص بس — الزيادة مش طلبية
                'shortfall' => $shortfall > 0 ? round($shortfall, 1) : 0.0,
                'coverDays' => $perDay > 0 ? (int) floor($balance / $perDay) : null,
            ];
        })
            ->filter(fn (array $row): bool => $row['shortfall'] > 0)
            ->sortByDesc('shortfall')
            ->take($limit)
            ->values()
            ->all();

        return [
            'rows' => $mapped,
            'historyDays' => $actualHistory,
            // أقل من شهر تاريخ = التوقّع مش موثوق، والأداة بتقول كده
            'reliable' => $actualHistory >= 30,
        ];
    }

    /**
     * مبيعات مقسّمة بالفترة، ومعاها نسبة التغيّر عن الفترة اللي قبلها.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: float, previousTotal: float, change: float|null}
     */
    public function salesBreakdown(string $from, string $to, string $groupBy = 'day'): array
    {
        $current = $this->sumSales($from, $to);

        // الفترة السابقة بنفس الطول بالظبط
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $length = (int) $start->diffInDays($end) + 1;

        $previousTo = $start->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($length - 1);

        $previousTotal = $this->sumSales(
            $previousFrom->toDateString(),
            $previousTo->toDateString(),
        )['total'];

        $invoices = Invoice::query()
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->whereBetween('date', [$from, $to])
            ->get(['date', 'total']);

        $buckets = [];

        foreach ($invoices as $invoice) {
            // التجميع في PHP — صيغ التاريخ في MySQL و SQLite مختلفة
            $key = match ($groupBy) {
                'month' => $invoice->date->format('Y-m'),
                'week' => $invoice->date->startOfWeek()->toDateString(),
                default => $invoice->date->toDateString(),
            };

            $buckets[$key] ??= ['period' => $key, 'count' => 0, 'total' => 0.0];
            $buckets[$key]['count']++;
            $buckets[$key]['total'] = round($buckets[$key]['total'] + (float) $invoice->total, 2);
        }

        ksort($buckets);

        return [
            'rows' => array_values($buckets),
            'total' => $current['total'],
            'count' => $current['count'],
            'previousTotal' => $previousTotal,
            'change' => $this->percentChange($previousTotal, $current['total']),
            'previousFrom' => $previousFrom->toDateString(),
            'previousTo' => $previousTo->toDateString(),
        ];
    }

    /**
     * أحسن التجار — بالمبيعات أو بالمحصّل أو بسرعة السداد.
     *
     * سرعة السداد = متوسط الأيام بين تاريخ الفاتورة وتاريخ التحصيل.
     * الأقل أحسن، فالترتيب بيبقى تصاعدي فيها.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bestCustomers(string $from, string $to, string $by = 'sales', int $limit = 10): array
    {
        $sales = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->whereBetween('invoices.date', [$from, $to])
            ->groupBy('customers.id', 'customers.name')
            ->selectRaw('customers.id, customers.name')
            ->selectRaw('count(*) as invoices, sum(invoices.total) as sales, sum(invoices.paid) as paid')
            ->get();

        $mapped = $sales->map(function ($row) use ($from, $to): array {
            $collected = (float) DB::table('payments')
                ->where('customer_id', $row->id)
                ->where('type', Payment::TYPE_RECEIPT)
                ->whereBetween('date', [$from, $to])
                ->sum('amount');

            return [
                'name' => $row->name,
                'invoices' => (int) $row->invoices,
                'sales' => round((float) $row->sales, 2),
                'collected' => round($collected, 2),
                'outstanding' => round((float) $row->sales - (float) $row->paid, 2),
                'paymentDays' => $this->averagePaymentDays((int) $row->id, $from, $to),
            ];
        })->all();

        usort($mapped, fn (array $a, array $b): int => match ($by) {
            'collected' => $b['collected'] <=> $a['collected'],
            // الأسرع في السداد الأول؛ اللي مالوش تحصيل يروح الآخر
            'speed' => ($a['paymentDays'] ?? PHP_INT_MAX) <=> ($b['paymentDays'] ?? PHP_INT_MAX),
            default => $b['sales'] <=> $a['sales'],
        });

        return array_slice($mapped, 0, $limit);
    }

    /**
     * مقارنة فترتين وتطلّع أكبر الانخفاضات.
     *
     * بترجع التلات زوايا مع بعض — صنف وتاجر ومندوب — عشان السؤال
     * «المبيعات نزلت ليه؟» يترد عليه من مرة واحدة بدل تلات نداءات.
     *
     * @return array<string, mixed>
     */
    public function salesDecline(
        string $currentFrom,
        string $currentTo,
        string $previousFrom,
        string $previousTo,
        int $limit = 8,
    ): array {
        $current = $this->sumSales($currentFrom, $currentTo);
        $previous = $this->sumSales($previousFrom, $previousTo);

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $this->percentChange($previous['total'], $current['total']),
            'byProduct' => $this->drops(
                $this->salesBy('products.name', 'invoice_items', $previousFrom, $previousTo),
                $this->salesBy('products.name', 'invoice_items', $currentFrom, $currentTo),
                $limit,
            ),
            'byCustomer' => $this->drops(
                $this->salesBy('customers.name', 'customers', $previousFrom, $previousTo),
                $this->salesBy('customers.name', 'customers', $currentFrom, $currentTo),
                $limit,
            ),
            'byRep' => $this->drops(
                $this->salesBy('sales_reps.name', 'sales_reps', $previousFrom, $previousTo),
                $this->salesBy('sales_reps.name', 'sales_reps', $currentFrom, $currentTo),
                $limit,
            ),
        ];
    }

    /**
     * @return array{total: float, count: int}
     */
    private function sumSales(string $from, string $to): array
    {
        $row = DB::table('invoices')
            ->where('type', Invoice::TYPE_SALE)
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('coalesce(sum(total), 0) as total, count(*) as count')
            ->first();

        return [
            'total' => round((float) $row->total, 2),
            'count' => (int) $row->count,
        ];
    }

    /**
     * مبيعات مجمّعة على بُعد واحد (صنف/تاجر/مندوب).
     *
     * @return array<string, float>
     */
    private function salesBy(string $label, string $dimension, string $from, string $to): array
    {
        $query = DB::table('invoices')
            ->where('invoices.type', Invoice::TYPE_SALE)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->whereBetween('invoices.date', [$from, $to]);

        if ($dimension === 'invoice_items') {
            $query->join('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->join('products', 'products.id', '=', 'invoice_items.product_id')
                ->selectRaw("{$label} as label, sum(invoice_items.total) as total");
        } elseif ($dimension === 'customers') {
            $query->join('customers', 'customers.id', '=', 'invoices.customer_id')
                ->selectRaw("{$label} as label, sum(invoices.total) as total");
        } else {
            $query->join('sales_reps', 'sales_reps.id', '=', 'invoices.sales_rep_id')
                ->selectRaw("{$label} as label, sum(invoices.total) as total");
        }

        return $query->groupBy('label')
            ->pluck('total', 'label')
            ->map(fn ($value): float => round((float) $value, 2))
            ->all();
    }

    /**
     * أكبر الانخفاضات بين فترتين.
     *
     * @param  array<string, float>  $before
     * @param  array<string, float>  $after
     * @return array<int, array<string, mixed>>
     */
    private function drops(array $before, array $after, int $limit): array
    {
        $rows = [];

        foreach ($before as $label => $was) {
            $now = $after[$label] ?? 0.0;
            $delta = round($now - $was, 2);

            // الانخفاضات بس
            if ($delta >= 0) {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'before' => $was,
                'after' => $now,
                'delta' => $delta,
                'change' => $this->percentChange($was, $now),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $a['delta'] <=> $b['delta']);

        return array_slice($rows, 0, $limit);
    }

    /** متوسط الأيام بين الفاتورة والتحصيل */
    private function averagePaymentDays(int $customerId, string $from, string $to): ?int
    {
        $payments = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.customer_id', $customerId)
            ->where('payments.type', Payment::TYPE_RECEIPT)
            ->whereBetween('payments.date', [$from, $to])
            ->get(['payments.date as paid_at', 'invoices.date as billed_at']);

        if ($payments->isEmpty()) {
            return null;
        }

        $days = $payments->map(fn ($row): int => (int) Carbon::parse($row->billed_at)
            ->diffInDays(Carbon::parse($row->paid_at)));

        return (int) round($days->avg());
    }

    /** نسبة التغيّر — null لو مفيش أساس نقارن عليه */
    private function percentChange(float $before, float $after): ?float
    {
        if ($before <= 0.0) {
            return null;
        }

        return round(($after - $before) / $before * 100, 1);
    }
}
