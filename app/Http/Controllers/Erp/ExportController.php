<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\CustomerService;
use App\Services\ExportBuilder;
use App\Services\PdfService;
use App\Services\ReportService;
use App\Services\SpreadsheetService;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
    private const REPORTS = ['period', 'product', 'customer', 'inventory'];

    public function __construct(
        private readonly ReportService $reports,
        private readonly CustomerService $customers,
        private readonly SupplierService $suppliers,
        private readonly ExportBuilder $builder,
        private readonly PdfService $pdf,
        private readonly SpreadsheetService $excel,
    ) {}

    public function report(Request $request): Response
    {
        $report = in_array($request->query('report'), self::REPORTS, true)
            ? $request->query('report')
            : 'period';

        $from = $request->query('from', Carbon::today()->subDays(89)->toDateString());
        $to = $request->query('to', Carbon::today()->toDateString());
        $groupBy = $request->query('groupBy') === 'month' ? 'month' : 'day';

        $data = match ($report) {
            'product' => ['rows' => $this->reports->salesByProduct($from, $to)],
            'customer' => ['rows' => $this->reports->salesByCustomer($from, $to)],
            'inventory' => $this->reports->inventoryValuation(),
            default => $this->reports->salesByPeriod($from, $to, $groupBy),
        };

        $title = ExportBuilder::REPORT_TITLES[$report];

        // تقييم المخزون لقطة لحظية — الفترة مالهاش معنى فيه
        $range = $report === 'inventory' ? null : $this->range($from, $to);

        return $this->send($request, $title, $range, fn (bool $raw): array => $this->builder->report($report, $data, $raw));
    }

    public function customerStatement(Request $request, Customer $customer): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $statement = $this->customers->statement($customer, $from, $to);

        return $this->send(
            $request,
            'كشف حساب — '.$customer->name,
            $from || $to ? $this->range($from, $to) : null,
            fn (bool $raw): array => $this->builder->statement($statement, $raw),
        );
    }

    public function supplierStatement(Request $request, Supplier $supplier): Response
    {
        $statement = $this->suppliers->statement($supplier);

        return $this->send(
            $request,
            'كشف حساب مورد — '.$supplier->name,
            null,
            fn (bool $raw): array => $this->builder->statement($statement, $raw),
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
}
