<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Services\ExportBuilder;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): Response
    {
        $filters = $this->params($request);

        return Inertia::render('reports/Index', [
            'report' => $filters['report'],
            'filters' => $filters,
            'data' => $this->build($filters),
            'reports' => $this->reportOptions(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'التقارير', 'href' => route('reports.index')],
            ],
        ]);
    }

    /** نسخة للطباعة / الحفظ PDF */
    public function print(Request $request): Response
    {
        $filters = $this->params($request);

        return Inertia::render('print/Report', [
            'company' => CompanyProfile::current()->present(),
            'report' => $filters['report'],
            'filters' => $filters,
            'data' => $this->build($filters),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * فلاتر التقرير بعد التحقق منها — بيقراها التحميل كمان.
     *
     * @return array{report: string, from: string, to: string, groupBy: string, search: string|null}
     */
    public function filtersFor(Request $request): array
    {
        return $this->params($request);
    }

    /**
     * @return array{report: string, from: string, to: string, groupBy: string, search: string|null}
     */
    private function params(Request $request): array
    {
        $report = $request->query('report', 'period');

        if (! array_key_exists($report, ExportBuilder::REPORT_TITLES)) {
            $report = 'period';
        }

        $search = $request->query('search');

        return [
            'report' => $report,
            'from' => $request->query('from', Carbon::today()->subDays(89)->toDateString()),
            'to' => $request->query('to', Carbon::today()->toDateString()),
            'groupBy' => $request->query('groupBy') === 'month' ? 'month' : 'day',
            'search' => filled($search) ? trim((string) $search) : null,
        ];
    }

    /**
     * بيانات التقرير.
     *
     * مشتركة بين الشاشة وصفحة الطباعة والتحميل — نفس الخدمة ونفس
     * الفلاتر، فالتلاتة بيقولوا نفس الأرقام.
     *
     * @param  array{report: string, from: string, to: string, groupBy: string, search: string|null}  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        ['report' => $report, 'from' => $from, 'to' => $to, 'search' => $search] = $filters;

        return match ($report) {
            'product' => ['rows' => $this->reports->salesByProduct($from, $to, 100, $search)],
            'customer' => ['rows' => $this->reports->salesByCustomer($from, $to, $search)],
            'inventory' => $this->reports->inventoryValuation($search),
            'sale_invoices' => $this->reports->invoiceRows(Invoice::TYPE_SALE, $from, $to, $search),
            'purchase_invoices' => $this->reports->invoiceRows(Invoice::TYPE_PURCHASE, $from, $to, $search),
            default => $this->reports->salesByPeriod($from, $to, $filters['groupBy'], $search),
        };
    }

    /**
     * قايمة التقارير للمنتقي — من مصدر واحد مع التحميل.
     *
     * @return array<int, array{value: string, label: string, snapshot: bool}>
     */
    private function reportOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => [
                'value' => $value,
                'label' => $label,
                // تقييم المخزون مالوش فترة، فخانات التاريخ بتختفي معاه
                'snapshot' => in_array($value, ExportBuilder::SNAPSHOT_REPORTS, true),
            ],
            array_keys(ExportBuilder::REPORT_TITLES),
            array_values(ExportBuilder::REPORT_TITLES),
        );
    }
}
