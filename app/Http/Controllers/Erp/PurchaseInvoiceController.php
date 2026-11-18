<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\AttachmentService;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    /** أسعار صرف تقريبية بتتقترح في الفورم، والمستخدم يقدر يعدّلها */
    private const SUGGESTED_RATES = ['EGP' => 1, 'USD' => 49.4, 'EUR' => 53.8, 'TRY' => 1.46];

    public function __construct(
        private readonly PurchaseService $purchases,
        private readonly AttachmentService $attachments,
        private readonly ProductService $products,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'supplier_id', 'currency', 'from', 'to']);

        $query = Invoice::query()
            ->where('type', Invoice::TYPE_PURCHASE)
            ->with(['supplier:id,name,country', 'warehouse:id,name'])
            ->when(filled($filters['search'] ?? null), fn (Builder $q) => $q->where(function (Builder $i) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $i->where('number', 'like', $term)
                    ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', $term));
            }))
            ->when(filled($filters['supplier_id'] ?? null), fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']))
            ->when(filled($filters['currency'] ?? null), fn (Builder $q) => $q->where('currency', $filters['currency']))
            ->when(filled($filters['from'] ?? null), fn (Builder $q) => $q->where('date', '>=', Carbon::parse($filters['from'])->toDateString()))
            ->when(filled($filters['to'] ?? null), fn (Builder $q) => $q->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString()))
            ->latest('date')
            ->latest('id');

        return Inertia::render('purchases/Index', [
            'invoices' => $query->paginate(20)->withQueryString()->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'date' => $invoice->date->toDateString(),
                'supplier' => $invoice->supplier?->name,
                'country' => $invoice->supplier?->country,
                'warehouse' => $invoice->warehouse?->name,
                'currency' => $invoice->currency,
                'exchangeRate' => (float) $invoice->exchange_rate,
                'total' => (float) $invoice->total,
                'totalEgp' => round((float) $invoice->total * (float) $invoice->exchange_rate, 2),
                'landed' => (float) $invoice->shipping_cost + (float) $invoice->customs_cost + (float) $invoice->clearance_cost,
                'remaining' => $invoice->remaining,
            ]),
            'hasAny' => Invoice::query()->purchases()->exists(),
            'filters' => $filters,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المشتريات', 'href' => route('purchases.index')],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('purchases/Create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name', 'country', 'currency']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $this->products->options(),
            // بيغذّوا نافذة «صنف جديد» اللي جوه الفورم
            'categories' => $this->categories->options(),
            'units' => Product::UNITS,
            'suggestedSku' => $this->products->suggestSku(),
            'rates' => self::SUGGESTED_RATES,
            'today' => now()->toDateString(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المشتريات', 'href' => route('purchases.index')],
                ['title' => 'فاتورة شراء جديدة', 'href' => route('purchases.create')],
            ],
        ]);
    }

    public function store(StorePurchaseInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->purchases->createPurchase($request->validated(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حفظ فاتورة الشراء '.$invoice->number.' وإضافة الكميات للمخزن وتحديث تكلفة الأصناف.',
        ]);

        return redirect()->route('purchases.show', $invoice);
    }

    public function show(Invoice $purchase): Response
    {
        abort_unless($purchase->type === Invoice::TYPE_PURCHASE, 404);

        return Inertia::render('purchases/Show', [
            'invoice' => $this->present($purchase),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المشتريات', 'href' => route('purchases.index')],
                ['title' => $purchase->number, 'href' => route('purchases.show', $purchase)],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $invoice): array
    {
        $invoice->load([
            'items.product:id,sku,name,unit', 'supplier', 'warehouse:id,name',
            'user:id,name', 'payments', 'movements', 'attachments.user:id,name',
        ]);

        $rate = (float) $invoice->exchange_rate;
        $costByProduct = $invoice->movements->keyBy('product_id');

        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'date' => $invoice->date->toDateString(),
            'dueDate' => $invoice->due_date?->toDateString(),
            'currency' => $invoice->currency,
            'exchangeRate' => $rate,
            'subtotal' => (float) $invoice->subtotal,
            'discount' => (float) $invoice->discount,
            'shipping' => (float) $invoice->shipping_cost,
            'customs' => (float) $invoice->customs_cost,
            'clearance' => (float) $invoice->clearance_cost,
            'total' => (float) $invoice->total,
            'totalEgp' => round((float) $invoice->total * $rate, 2),
            'landedTotal' => (float) $invoice->shipping_cost + (float) $invoice->customs_cost + (float) $invoice->clearance_cost,
            'paid' => (float) $invoice->paid,
            'remaining' => $invoice->remaining,
            'notes' => $invoice->notes,
            'createdBy' => $invoice->user?->name,
            'warehouse' => $invoice->warehouse?->name,
            'supplier' => $invoice->supplier ? [
                'id' => $invoice->supplier->id,
                'name' => $invoice->supplier->name,
                'country' => $invoice->supplier->country,
                'phone' => $invoice->supplier->phone,
                'currency' => $invoice->supplier->currency,
            ] : null,
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'quantity' => (float) $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'total' => (float) $item->total,
                // التكلفة الواصلة بالجنيه زي ما اتسجّلت في حركة المخزون
                'landedUnitCost' => (float) ($costByProduct->get($item->product_id)?->unit_cost ?? 0),
            ]),
            'payments' => $invoice->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'date' => $payment->date->toDateString(),
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'reference' => $payment->reference,
            ]),
            'attachments' => $invoice->attachments->map(
                fn (Attachment $attachment) => $this->attachments->present($attachment)
            ),
        ];
    }
}
