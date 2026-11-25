<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreparationOrderRequest;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\PreparationOrder;
use App\Models\PreparationOrderItem;
use App\Models\Warehouse;
use App\Services\CustomerService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\PdfService;
use App\Services\PreparationOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * طلبات التحضير.
 *
 * **ولا سطر هنا بيطلّع سعر.** لا القائمة ولا الصفحة ولا الـ PDF. أي
 * تعديل جاي لازم يفضل كده: الورقة بتروح لعامل المخزن.
 */
class PreparationOrderController extends Controller
{
    public function __construct(
        private readonly PreparationOrderService $orders,
        private readonly InventoryService $inventory,
        private readonly CustomerService $customers,
        private readonly PdfService $pdf,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        $filters = $request->only(['search', 'status', 'customer_id', 'warehouse_id', 'from', 'to']);

        return Inertia::render('preparation-orders/Index', [
            'orders' => $this->orders->paginate($filters)
                ->through(fn (PreparationOrder $order) => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'date' => $order->date->toDateString(),
                    'status' => $order->status,
                    'statusLabel' => $order->statusLabel(),
                    'customer' => $order->customer?->name,
                    'warehouse' => $order->warehouse?->name,
                    'createdBy' => $order->user?->name,
                    'itemCount' => $order->items_count,
                    'invoiceId' => $order->invoice_id,
                ]),
            'hasAny' => PreparationOrder::query()->exists(),
            'filters' => $filters,
            'statuses' => $this->statusOptions(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        return Inertia::render('preparation-orders/Create', [
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'city']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            /*
             * الكتالوج بيجي بالمتاح لكل مخزن (الرصيد ناقص المحجوز) —
             * الصنف اللي متاحه صفر بيتعرض ومتقفل، والكمية ما تعدّيش
             * المتاح. نفس الأرقام اللي الحفظ بيتفحص عليها.
             */
            'products' => $this->priceFreeCatalogue(),
            'today' => now()->toDateString(),
            'breadcrumbs' => [...$this->breadcrumbs(), [
                'title' => 'طلب تحضير جديد',
                'href' => route('preparation-orders.create'),
            ]],
        ]);
    }

    public function store(StorePreparationOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->create($request->validated(), $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم فتح طلب التحضير '.$order->number.' وحجز الكميات.',
        ]);

        return redirect()->route('preparation-orders.show', $order);
    }

    public function show(PreparationOrder $preparationOrder): Response
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        return Inertia::render('preparation-orders/Show', [
            'order' => $this->present($preparationOrder),
            /*
             * رابط موقّع للـ PDF عشان زرار الواتساب.
             * الواتساب بيتفتح بـ wa.me من غير أي API، فاللينك اللي
             * بيتبعت لازم يفتح من غير تسجيل دخول — التوقيع هو اللي
             * بيحميه، وبيقع لوحده بعد أسبوع.
             */
            'pdfUrl' => $this->signedPdfUrl($preparationOrder),
            'breadcrumbs' => [...$this->breadcrumbs(), [
                'title' => $preparationOrder->number,
                'href' => route('preparation-orders.show', $preparationOrder),
            ]],
        ]);
    }

    /** المخزن جهّز الطلب — ومعاه الكميات اللي طلّعها فعلاً */
    public function prepare(Request $request, PreparationOrder $preparationOrder): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.prepared_quantity' => ['required', 'numeric', 'min:0'],
        ], [
            'items.*.prepared_quantity.min' => 'الكمية المحضّرة ماينفعش تكون بالسالب.',
        ]);

        $this->orders->markPrepared($preparationOrder, $data['items'], $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'الطلب '.$preparationOrder->number.' اتعلّم إنه اتحضّر.',
        ]);

        return back();
    }

    public function cancel(Request $request, PreparationOrder $preparationOrder): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        $this->orders->cancel($preparationOrder, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'الطلب '.$preparationOrder->number.' اتلغى والكميات اتفكّت.',
        ]);

        return back();
    }

    /**
     * ورقة التحضير A4 — للطباعة من المتصفح.
     *
     * فيها خانة «تم» فاضية جنب كل صنف عشان عامل المخزن يعلّم بالقلم
     * وهو بيجمّع، وتوقيعين تحت.
     */
    public function print(PreparationOrder $preparationOrder): Response
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        return Inertia::render('print/PreparationOrder', [
            'company' => CompanyProfile::current()->present(),
            'order' => $this->present($preparationOrder),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * نفس الورقة PDF.
     *
     * المسار ده بيتفتح بتوقيع من غير تسجيل دخول كمان — ده اللي بيخلّي
     * مشاركة الواتساب تشتغل من غير أي API: بنبعت لينك، واللي بيستلمه
     * بيفتحه على طول.
     */
    public function pdf(PreparationOrder $preparationOrder): HttpResponse
    {
        $order = $this->present($preparationOrder);

        return $this->pdf->download('pdf.preparation-order', [
            'title' => 'طلب تحضير '.$order['number'],
            'company' => CompanyProfile::current()->present(),
            'order' => $order,
            'printedAt' => now()->toDateString(),
        ], 'طلب تحضير '.$order['number']);
    }

    /**
     * فورم فاتورة البيع متملّي من الطلب.
     *
     * بنعيد استخدام نفس شاشة `sales/Create` — مش نسخة تانية منها.
     * الفاتورة اللي جاية من طلب لازم تمشي بنفس قواعد أي فاتورة بيع
     * (الضريبة، فحص المتاح، حد الائتمان)، ونسختين من الفورم كانوا
     * هيفرقوا عن بعض مع أول تعديل.
     */
    public function convert(PreparationOrder $preparationOrder): Response
    {
        Gate::authorize(ErpAbility::ManagePreparationOrders->value);

        abort_unless($preparationOrder->isEditable(), 404);

        $preparationOrder->load(['customer', 'warehouse']);

        return Inertia::render('sales/Create', [
            'customers' => $this->customers->invoiceOptions(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            // حجز الطلب ده متشال من الحساب — الفاتورة بتنفّذه مش بتزاحمه
            'products' => $this->catalogue($preparationOrder->id),
            'vatRate' => InvoiceService::VAT_RATE,
            'today' => now()->toDateString(),
            /*
             * الفورم بيتملّي من هنا. الأسعار بتتحسب من شريحة التاجر —
             * ده أول وآخر مكان بيدخل فيه سعر على طلب التحضير.
             */
            'prefill' => [
                'preparationOrderId' => $preparationOrder->id,
                'orderNumber' => $preparationOrder->number,
                'customerId' => $preparationOrder->customer_id,
                'warehouseId' => $preparationOrder->warehouse_id,
                'items' => $this->orders->invoiceLines($preparationOrder),
            ],
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'طلبات التحضير', 'href' => route('preparation-orders.index')],
                ['title' => $preparationOrder->number, 'href' => route('preparation-orders.show', $preparationOrder)],
                ['title' => 'تحويل لفاتورة بيع', 'href' => route('preparation-orders.convert', $preparationOrder)],
            ],
        ]);
    }

    /**
     * كتالوج الأصناف بالمتاح لكل مخزن — **من غير أسعار**.
     *
     * الكتالوج مشترك مع فاتورة البيع وفيه ٣ أعمدة أسعار. ده بالظبط
     * إزاي سعر بيوصل لشاشة المخزن من غير ما حد ياخد باله — فبنشيلها
     * هنا، مش بنعتمد على إن الواجهة مش هتعرضها.
     *
     * @return array<int, array<string, mixed>>
     */
    private function priceFreeCatalogue(): array
    {
        return $this->inventory->catalogueWithStock()
            ->map(fn (array $product): array => collect($product)
                ->except(['salePrice', 'wholesalePrice', 'purchasePrice'])
                ->all())
            ->all();
    }

    /**
     * الكتالوج الكامل بأسعاره — لفورم فاتورة البيع بس.
     *
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(?int $exceptOrderId = null): array
    {
        return $this->inventory->catalogueWithStock($exceptOrderId)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PreparationOrder $order): array
    {
        $order->load([
            'items.product:id,sku,name,unit',
            'customer:id,name,phone,city,address',
            'warehouse:id,name',
            'user:id,name',
            'invoice:id,number',
        ]);

        return [
            'id' => $order->id,
            'number' => $order->number,
            'date' => $order->date->toDateString(),
            'status' => $order->status,
            'statusLabel' => $order->statusLabel(),
            'isEditable' => $order->isEditable(),
            'notes' => $order->notes,
            'preparedAt' => $order->prepared_at?->toDateTimeString(),
            'cancelledAt' => $order->cancelled_at?->toDateTimeString(),
            'createdBy' => $order->user?->name,
            'warehouse' => $order->warehouse?->name,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
                'city' => $order->customer->city,
                'address' => $order->customer->address,
            ] : null,
            'invoice' => $order->invoice ? [
                'id' => $order->invoice->id,
                'number' => $order->invoice->number,
            ] : null,
            // ولا كلمة سعر — الكمية والوحدة وبس
            'items' => $order->items->map(fn (PreparationOrderItem $item) => [
                'id' => $item->id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'quantity' => (float) $item->quantity,
                'preparedQuantity' => $item->prepared_quantity !== null
                    ? (float) $item->prepared_quantity
                    : null,
            ])->all(),
        ];
    }

    /** رابط PDF موقّع وصالح أسبوع */
    private function signedPdfUrl(PreparationOrder $order): string
    {
        return URL::temporarySignedRoute(
            'preparation-orders.pdf',
            now()->addWeek(),
            ['preparationOrder' => $order->id],
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(PreparationOrder::STATUS_LABELS),
            array_values(PreparationOrder::STATUS_LABELS),
        );
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function breadcrumbs(): array
    {
        return [
            ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
            ['title' => 'طلبات التحضير', 'href' => route('preparation-orders.index')],
        ];
    }
}
