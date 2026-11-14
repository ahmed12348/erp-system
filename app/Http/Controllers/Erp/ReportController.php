<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /** التقارير المتاحة */
    private const REPORTS = ['period', 'product', 'customer', 'inventory'];

    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): Response
    {
        [$report, $from, $to, $groupBy] = $this->params($request);

        return Inertia::render('reports/Index', [
            'report' => $report,
            'filters' => ['from' => $from, 'to' => $to, 'groupBy' => $groupBy],
            'data' => $this->build($report, $from, $to, $groupBy),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'التقارير', 'href' => route('reports.index')],
            ],
        ]);
    }

    /** نسخة للطباعة / الحفظ PDF */
    public function print(Request $request): Response
    {
        [$report, $from, $to, $groupBy] = $this->params($request);

        return Inertia::render('print/Report', [
            'company' => config('app.name'),
            'report' => $report,
            'filters' => ['from' => $from, 'to' => $to, 'groupBy' => $groupBy],
            'data' => $this->build($report, $from, $to, $groupBy),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function params(Request $request): array
    {
        $report = $request->query('report', 'period');

        if (! in_array($report, self::REPORTS, true)) {
            $report = 'period';
        }

        return [
            $report,
            $request->query('from', Carbon::today()->subDays(89)->toDateString()),
            $request->query('to', Carbon::today()->toDateString()),
            $request->query('groupBy') === 'month' ? 'month' : 'day',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function build(string $report, string $from, string $to, string $groupBy): array
    {
        return match ($report) {
            'product' => ['rows' => $this->reports->salesByProduct($from, $to)],
            'customer' => ['rows' => $this->reports->salesByCustomer($from, $to)],
            'inventory' => $this->reports->inventoryValuation(),
            default => $this->reports->salesByPeriod($from, $to, $groupBy),
        };
    }
}
