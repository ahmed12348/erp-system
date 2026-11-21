<?php

namespace App\Http\Controllers\Erp;

use App\Concerns\AttachesScannedInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleInvoiceRequest;
use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Warehouse;
use App\Services\AttachmentService;
use App\Services\CustomerService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\SalesQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleInvoiceController extends Controller
{
    use AttachesScannedInvoice;

    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly SalesQueryService $sales,
        private readonly InventoryService $inventory,
        private readonly CustomerService $customers,
        private readonly AttachmentService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'customer_id', 'warehouse_id', 'eta', 'payment', 'from', 'to']);

        return Inertia::render('sales/Index', [
            'invoices' => $this->sales->paginate($filters)->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'type' => $invoice->type,
                'date' => $invoice->date->toDateString(),
                'customer' => $invoice->customer?->name,
                'warehouse' => $invoice->warehouse?->name,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid,
                'remaining' => $invoice->remaining,
                'etaStatus' => $invoice->eta_status,
            ]),
            'totals' => $this->sales->totals($filters),
            'hasAny' => Invoice::query()->sales()->exists(),
            'filters' => $filters,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المبيعات', 'href' => route('sales.index')],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sales/Create', [
            'customers' => Customer::active()->orderBy('name')->get([
                'id', 'name', 'city', 'price_tier', 'credit_limit',
            ])->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'city' => $customer->city,
                'priceTier' => $customer->price_tier,
                'creditLimit' => $customer->credit_limit ? (float) $customer->credit_limit : null,
                'balance' => $this->customers->balanceFor($customer),
            ]),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $this->inventory->catalogueWithStock(),
            'vatRate' => InvoiceService::VAT_RATE,
            'today' => now()->toDateString(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المبيعات', 'href' => route('sales.index')],
                ['title' => 'فاتورة بيع جديدة', 'href' => route('sales.create')],
            ],
        ]);
    }

    public function store(StoreSaleInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->createSale(
            $request->safe()->except('attachment'),
            $request->user(),
        );

        $this->attachScan($request, $invoice);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حفظ الفاتورة '.$invoice->number.' وخصم الكميات من المخزن.',
        ]);

        return redirect()->route('sales.show', $invoice);
    }

    public function show(Invoice $sale): Response
    {
        abort_unless(in_array($sale->type, [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN], true), 404);

        return Inertia::render('sales/Show', [
            'invoice' => $this->present($sale),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المبيعات', 'href' => route('sales.index')],
                ['title' => $sale->number, 'href' => route('sales.show', $sale)],
            ],
        ]);
    }

    public function print(Invoice $sale): Response
    {
        abort_unless(in_array($sale->type, [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN], true), 404);

        return Inertia::render('print/SaleInvoice', [
            'company' => config('app.name'),
            'invoice' => $this->present($sale),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $invoice): array
    {
        $invoice->load([
            'items.product:id,sku,name,unit', 'customer', 'warehouse:id,name',
            'user:id,name', 'payments', 'attachments.user:id,name',
        ]);

        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'type' => $invoice->type,
            'date' => $invoice->date->toDateString(),
            'dueDate' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
            'currency' => $invoice->currency,
            'subtotal' => (float) $invoice->subtotal,
            'discount' => (float) $invoice->discount,
            'tax' => (float) $invoice->tax,
            'total' => (float) $invoice->total,
            'paid' => (float) $invoice->paid,
            'remaining' => $invoice->remaining,
            'notes' => $invoice->notes,
            'etaStatus' => $invoice->eta_status,
            'etaUuid' => $invoice->eta_uuid,
            'createdBy' => $invoice->user?->name,
            'warehouse' => $invoice->warehouse?->name,
            'customer' => $invoice->customer ? [
                'id' => $invoice->customer->id,
                'name' => $invoice->customer->name,
                'phone' => $invoice->customer->phone,
                'city' => $invoice->customer->city,
                'address' => $invoice->customer->address,
            ] : null,
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'quantity' => (float) $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'total' => (float) $item->total,
            ]),
            'payments' => $invoice->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'date' => $payment->date->toDateString(),
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
            ]),
            'attachments' => $invoice->attachments->map(
                fn (Attachment $attachment) => $this->attachments->present($attachment)
            ),
        ];
    }
}
