<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\CustomerService;
use App\Services\ExportBuilder;
use App\Services\PdfService;
use App\Services\PurchaseQueryService;
use App\Services\SalesQueryService;
use App\Services\SpreadsheetService;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تحميل التقارير والكشوف والفواتير PDF أو إكسيل.
 *
 * صفحات الطباعة فاضلة زي ما هي — الطباعة من المتصفح أسرع لما تكون
 * عايز ورقة في إيدك، والتحميل بيفيد لما تكون عايز تبعت الملف أو
 * تحفظه. الاتنين بياخدوا بياناتهم من نفس الخدمات، فالأرقام واحدة.
 */
class ExportController extends Controller
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly SupplierService $suppliers,
        private readonly SalesQueryService $sales,
        private readonly PurchaseQueryService $purchases,
        private readonly ExportBuilder $builder,
        private readonly PdfService $pdf,
        private readonly SpreadsheetService $excel,
    ) {}

    /**
     * تحميل تقرير.
     *
     * الفلاتر والبيانات بتتبني بنفس كود الشاشة (`ReportController`) —
     * نسختين من نفس التقرير كانوا هيفرقوا عن بعض مع أول تعديل، والمستخدم
     * هو اللي يكتشف.
     */
    public function report(Request $request): Response
    {
        $screen = app(ReportController::class);

        $filters = $screen->filtersFor($request);
        $data = $screen->build($filters);
        $report = $filters['report'];

        return $this->send(
            $request,
            ExportBuilder::REPORT_TITLES[$report],
            $this->reportRange($filters),
            fn (bool $raw): array => $this->builder->report($report, $data, $raw),
        );
    }

    /**
     * سطر الفلاتر في ترويسة الملف.
     *
     * تقييم المخزون لقطة لحظية — الفترة مالهاش معنى فيه. والبحث برقم
     * الفاتورة لازم يتكتب، وإلا اللي بيستلم الملف يحسبه التقرير كامل.
     *
     * @param  array{report: string, from: string, to: string, groupBy: string, search: string|null}  $filters
     */
    private function reportRange(array $filters): ?string
    {
        $parts = array_filter([
            in_array($filters['report'], ExportBuilder::SNAPSHOT_REPORTS, true)
                ? null
                : $this->range($filters['from'], $filters['to']),
            filled($filters['search']) ? 'رقم الفاتورة: '.$filters['search'] : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public function customerStatement(Request $request, Customer $customer): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $search = $request->query('search');

        $statement = $this->customers->statement($customer, $from, $to, $search);

        return $this->send(
            $request,
            'كشف حساب — '.$customer->name,
            $this->statementRange($from, $to, $search),
            fn (bool $raw): array => $this->builder->statement($statement, $raw),
        );
    }

    public function supplierStatement(Request $request, Supplier $supplier): Response
    {
        $search = $request->query('search');

        $statement = $this->suppliers->statement($supplier, $search);

        return $this->send(
            $request,
            'كشف حساب مورد — '.$supplier->name,
            $this->statementRange(null, null, $search),
            fn (bool $raw): array => $this->builder->statement($statement, $raw),
        );
    }

    /**
     * قائمة فواتير البيع بفلاتر الشاشة.
     *
     * بتاخد الاستعلام من نفس الخدمة اللي بتغذّي الشاشة، فاللي في الملف
     * هو اللي قدام المستخدم بالظبط — وكل النتيجة مش الصفحة اللي هو
     * واقف عليها.
     */
    public function saleList(Request $request): Response
    {
        $filters = $request->only(['search', 'customer_id', 'warehouse_id', 'eta', 'payment', 'status', 'from', 'to']);

        $rows = $this->sales->query($filters)->get()
            ->map(fn (Invoice $invoice): array => [
                'number' => $invoice->number,
                'type' => $invoice->type,
                'status' => $invoice->status,
                'date' => $invoice->date->toDateString(),
                'customer' => $invoice->customer?->name,
                'warehouse' => $invoice->warehouse?->name,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid,
                'remaining' => $invoice->remaining,
            ]);

        $totals = $this->sales->totals($filters);

        return $this->send(
            $request,
            'فواتير المبيعات',
            $this->listRange($filters),
            fn (bool $raw): array => $this->builder->saleList($rows, $totals, $raw),
        );
    }

    /** قائمة فواتير الشراء بفلاتر الشاشة */
    public function purchaseList(Request $request): Response
    {
        $filters = $request->only(['search', 'supplier_id', 'currency', 'status', 'from', 'to']);

        $rows = $this->purchases->query($filters)->get()
            ->map(fn (Invoice $invoice): array => $this->purchases->row($invoice));

        return $this->send(
            $request,
            'فواتير المشتريات',
            $this->listRange($filters),
            fn (bool $raw): array => $this->builder->purchaseList($rows, $raw),
        );
    }

    public function invoice(Request $request, Invoice $invoice): Response
    {
        abort_if($invoice->isDraft(), 404);

        $invoice->load(['items.product:id,sku,name,unit', 'customer', 'supplier']);

        $data = [
            'currency' => $invoice->currency,
            'subtotal' => (float) $invoice->subtotal,
            'discount' => (float) $invoice->discount,
            'tax' => (float) $invoice->tax,
            'total' => (float) $invoice->total,
            'paid' => (float) $invoice->paid,
            'remaining' => $invoice->remaining,
            'items' => $invoice->items->map(fn ($item): array => [
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'quantity' => (float) $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])->all(),
        ];

        $party = $invoice->customer?->name ?? $invoice->supplier?->name ?? '—';

        $title = ($invoice->type === Invoice::TYPE_PURCHASE ? 'فاتورة شراء ' : 'فاتورة بيع ')
            .$invoice->number;

        return $this->send(
            $request,
            $title,
            $party.' · '.$invoice->date->toDateString(),
            fn (bool $raw): array => $this->builder->invoice($data, $raw),
        );
    }

    /**
     * بيبعت PDF أو إكسيل حسب `format`.
     *
     * الـ callback بياخد `$raw`: الإكسيل عايز أرقام خام يقدر يجمعها،
     * والـ PDF عايز نص منسّق بالعملة.
     *
     * @param  callable(bool): array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}  $build
     */
    private function send(Request $request, string $title, ?string $range, callable $build): Response
    {
        if ($request->query('format') === 'excel') {
            $table = $build(true);

            return $this->excel->download($title, $table['headers'], $table['rows']);
        }

        $table = $build(false);

        return $this->pdf->download('pdf.table', [
            'title' => $title,
            'company' => config('app.name'),
            'range' => $range,
            'printedAt' => now()->toDateString(),
            'headers' => $table['headers'],
            'rows' => $table['rows'],
            'numeric' => $table['numeric'],
            'summary' => $table['summary'],
        ], $title);
    }

    private function range(?string $from, ?string $to): string
    {
        return 'من '.($from ?: '—').' إلى '.($to ?: '—');
    }

    /**
     * سطر الفلاتر اللي بيتكتب في ترويسة ملف القائمة.
     *
     * الملف لازم يقول بأنهي فلاتر اتعمل، وإلا حد بيستلمه بيحسبه كل
     * الفواتير ويسأل فين الباقي.
     *
     * @param  array<string, mixed>  $filters
     */
    private function listRange(array $filters): ?string
    {
        $parts = array_filter([
            ($filters['from'] ?? null) || ($filters['to'] ?? null)
                ? $this->range($filters['from'] ?? null, $filters['to'] ?? null)
                : null,
            filled($filters['search'] ?? null) ? 'بحث: '.$filters['search'] : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }

    /**
     * سطر الفترة بتاع الكشف — ومعاه البحث لو المستخدم كان مفلتر.
     *
     * الملف لازم يقول إنه مفلتر، وإلا حد بيستلم الكشف يحسبه كامل ويسأل
     * فين باقي الحركة.
     */
    private function statementRange(?string $from, ?string $to, ?string $search): ?string
    {
        $parts = array_filter([
            $from || $to ? $this->range($from, $to) : null,
            filled($search) ? 'بحث: '.$search : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
