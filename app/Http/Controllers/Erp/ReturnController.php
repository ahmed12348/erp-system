<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnRequest;
use App\Models\Attachment;
use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Warehouse;
use App\Services\AttachmentService;
use App\Services\EtaService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * المرتجعات — بيع وشراء.
 */
class ReturnController extends Controller
{
    public function __construct(
        private readonly ReturnService $returns,
        private readonly InventoryService $inventory,
        private readonly AttachmentService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['type', 'search', 'reason', 'orphan', 'from', 'to']);
        $type = ($filters['type'] ?? null) === Invoice::TYPE_PURCHASE_RETURN
            ? Invoice::TYPE_PURCHASE_RETURN
            : Invoice::TYPE_SALE_RETURN;

        $filters['type'] = $type;

        return Inertia::render('returns/Index', [
            'returns' => $this->returns->paginate($filters)
                ->through(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date->toDateString(),
                    'party' => $invoice->customer?->name ?? $invoice->supplier?->name,
                    'warehouse' => $invoice->warehouse?->name,
                    'original' => $invoice->originalInvoice?->number,
                    'originalId' => $invoice->original_invoice_id,
                    'reason' => $invoice->returnReasonLabel(),
                    'isOrphan' => (bool) $invoice->is_orphan_return,
                    'total' => (float) $invoice->total,
                ]),
            'type' => $type,
            'hasAny' => Invoice::query()->where('type', $type)->exists(),
            'filters' => $filters,
            'reasons' => $this->reasonOptions(),
            'breadcrumbs' => $this->breadcrumbs($type),
        ]);
    }

    /** فورم المرتجع على فاتورة موجودة */
    public function create(Invoice $invoice): Response
    {
        abort_if($invoice->isReturn(), 404);
        abort_unless($invoice->status === Invoice::STATUS_CONFIRMED, 404);

        $invoice->load(['customer:id,name', 'supplier:id,name', 'warehouse:id,name']);

        return Inertia::render('returns/Create', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'type' => $invoice->type,
                'date' => $invoice->date->toDateString(),
                'party' => $invoice->customer?->name ?? $invoice->supplier?->name,
                'warehouse' => $invoice->warehouse?->name,
                'currency' => $invoice->currency,
                'total' => (float) $invoice->total,
            ],
            // السقف لكل بند = المباع ناقص المرتجع قبل كده
            'lines' => $this->returns->returnableLines($invoice),
            'reasons' => $this->reasonOptions(),
            'methods' => $this->methodOptions(),
            'today' => now()->toDateString(),
            'breadcrumbs' => [
                ...$this->breadcrumbs($invoice->type === Invoice::TYPE_PURCHASE
                    ? Invoice::TYPE_PURCHASE_RETURN
                    : Invoice::TYPE_SALE_RETURN),
                ['title' => 'مرتجع على '.$invoice->number, 'href' => route('returns.create', $invoice)],
            ],
        ]);
    }

    public function store(StoreReturnRequest $request, Invoice $invoice): RedirectResponse
    {
        $return = $this->returns->createFor($invoice, $request->validated(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تسجيل المرتجع '.$return->number.' وتعديل المخزون والرصيد.',
        ]);

        return redirect()->route('returns.show', $return);
    }

    /**
     * مرتجع من غير فاتورة أصلية — للمدير بس.
     *
     * الصلاحية هنا `ManageUsers` عن قصد: دي أضيق صلاحية في النظام
     * والمدير لوحده اللي عنده. مرتجع من غير أصل مابينفعش يتطابق مع أي
     * حاجة، فمحدش غيره يعمله.
     */
    public function createOrphan(Request $request): Response
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $type = $request->query('type') === Invoice::TYPE_PURCHASE_RETURN
            ? Invoice::TYPE_PURCHASE_RETURN
            : Invoice::TYPE_SALE_RETURN;

        return Inertia::render('returns/CreateOrphan', [
            'type' => $type,
            'parties' => $this->returns->orphanParties($type),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $this->inventory->catalogueWithStock()->all(),
            'reasons' => $this->reasonOptions(),
            'methods' => $this->methodOptions(),
            'today' => now()->toDateString(),
            'breadcrumbs' => [
                ...$this->breadcrumbs($type),
                ['title' => 'مرتجع من غير فاتورة', 'href' => route('returns.orphan.create')],
            ],
        ]);
    }

    public function storeOrphan(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $data = $request->validate([
            'type' => ['required', Rule::in(Invoice::RETURN_TYPES)],
            'customer_id' => ['nullable', 'required_if:type,sale_return', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'required_if:type,purchase_return', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'return_reason' => ['required', Rule::in(array_keys(Invoice::RETURN_REASONS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'refund' => ['nullable', 'array'],
            'refund.amount' => ['nullable', 'numeric', 'min:0'],
            'refund.method' => ['nullable', Rule::in(PaymentService::METHODS)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'customer_id.required_if' => 'اختار التاجر.',
            'supplier_id.required_if' => 'اختار المورد.',
            'return_reason.required' => 'اختار سبب المرتجع.',
        ]);

        $return = $this->returns->createOrphan($data, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تسجيل المرتجع '.$return->number.' من غير فاتورة أصلية.',
        ]);

        return redirect()->route('returns.show', $return);
    }

    public function show(Invoice $return): Response
    {
        abort_unless($return->isReturn(), 404);

        return Inertia::render('returns/Show', [
            'doc' => $this->present($return),
            'breadcrumbs' => [
                ...$this->breadcrumbs($return->type),
                ['title' => $return->number, 'href' => route('returns.show', $return)],
            ],
        ]);
    }

    /**
     * إرسال المرتجع للمنظومة كإشعار خصم.
     *
     * ليه أكشن لوحده مش `EtaController`: الربط الضمني بيطابق اسم
     * متغيّر المسار باسم بارامتر الدالة، و`{return}` ما كانش بيتربط بـ
     * `$sale` — فالأكشن كان بياخد فاتورة فاضية ويرجّع redirect كأن كل
     * حاجة تمام من غير ما يبعت حاجة.
     */
    public function sendToEta(Invoice $return): RedirectResponse
    {
        abort_unless($return->isReturn(), 404);

        $invoice = app(EtaService::class)->send($return);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إرسال إشعار الخصم '.$invoice->number
                .' للمنظومة. الرقم المرجعي: '.$invoice->eta_uuid,
        ]);

        return back();
    }

    public function print(Invoice $return): Response
    {
        abort_unless($return->isReturn(), 404);

        return Inertia::render('print/Return', [
            'company' => CompanyProfile::current()->present(),
            'doc' => $this->present($return),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $return): array
    {
        $return->load([
            'items.product:id,sku,name,unit',
            'customer', 'supplier', 'warehouse:id,name', 'user:id,name',
            'originalInvoice:id,number,type,date',
            'payments',
            'attachments.user:id,name',
        ]);

        return [
            'id' => $return->id,
            'number' => $return->number,
            'type' => $return->type,
            'date' => $return->date->toDateString(),
            'currency' => $return->currency,
            'subtotal' => (float) $return->subtotal,
            'tax' => (float) $return->tax,
            'total' => (float) $return->total,
            'reason' => $return->returnReasonLabel(),
            'reasonKey' => $return->return_reason,
            'notes' => $return->notes,
            'isOrphan' => (bool) $return->is_orphan_return,
            'etaStatus' => $return->eta_status,
            'etaUuid' => $return->eta_uuid,
            'createdBy' => $return->user?->name,
            'warehouse' => $return->warehouse?->name,
            'party' => $return->customer?->name ?? $return->supplier?->name,
            'partyLink' => $return->customer_id
                ? "/customers/{$return->customer_id}"
                : ($return->supplier_id ? "/suppliers/{$return->supplier_id}" : null),
            'original' => $return->originalInvoice ? [
                'id' => $return->originalInvoice->id,
                'number' => $return->originalInvoice->number,
                'date' => $return->originalInvoice->date->toDateString(),
                'link' => $return->originalInvoice->type === Invoice::TYPE_PURCHASE
                    ? "/purchases/{$return->originalInvoice->id}"
                    : "/sales/{$return->originalInvoice->id}",
            ] : null,
            'items' => $return->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'quantity' => (float) $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])->all(),
            // الرد النقدي لو حصل
            'refunds' => $return->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'date' => $payment->date->toDateString(),
                'amount' => (float) $payment->amount,
                'method' => PaymentService::METHOD_LABELS[$payment->method] ?? $payment->method,
            ])->all(),
            'attachments' => $return->attachments->map(
                fn (Attachment $attachment) => $this->attachments->present($attachment)
            )->all(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function reasonOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(Invoice::RETURN_REASONS),
            array_values(Invoice::RETURN_REASONS),
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function methodOptions(): array
    {
        return array_map(
            fn (string $value): array => [
                'value' => $value,
                'label' => PaymentService::METHOD_LABELS[$value] ?? $value,
            ],
            PaymentService::METHODS,
        );
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function breadcrumbs(string $type): array
    {
        $isPurchase = $type === Invoice::TYPE_PURCHASE_RETURN;

        return [
            ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
            [
                'title' => $isPurchase ? 'مرتجعات المشتريات' : 'مرتجعات المبيعات',
                'href' => route('returns.index', $isPurchase ? ['type' => $type] : []),
            ],
        ];
    }
}
