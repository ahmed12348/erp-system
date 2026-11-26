<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\StockDocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * أذون الإضافة والخصم.
 *
 * الكتابة لأي حد عنده `WriteStockDocuments`، والاعتماد للمدير والمحاسب
 * (`ManageMasterData`) — والاعتماد هو اللي بيحرّك البضاعة.
 */
class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockDocumentService $documents,
        private readonly InventoryService $inventory,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $filters = $request->only(['type', 'status', 'reason', 'warehouse_id', 'search', 'from', 'to']);

        $query = StockAdjustment::query()
            ->with(['warehouse:id,name', 'user:id,name', 'items'])
            ->when(filled($filters['type'] ?? null), fn (Builder $q) => $q->where('type', $filters['type']))
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['reason'] ?? null), fn (Builder $q) => $q->where('reason', $filters['reason']))
            ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['search'] ?? null), fn (Builder $q) => $q->where('number', 'like', '%'.$filters['search'].'%'))
            ->when(filled($filters['from'] ?? null), fn (Builder $q) => $q->where('date', '>=', Carbon::parse($filters['from'])->toDateString()))
            ->when(filled($filters['to'] ?? null), fn (Builder $q) => $q->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString()))
            ->latest('date')
            ->latest('id');

        return Inertia::render('stock/adjustments/Index', [
            'adjustments' => $query->paginate(20)->withQueryString()
                ->through(fn (StockAdjustment $adjustment) => $this->row($adjustment)),
            'hasAny' => StockAdjustment::query()->exists(),
            'filters' => $filters,
            'reasons' => $this->reasonOptions(),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'canApprove' => $request->user()?->allows(ErpAbility::ManageMasterData) ?? false,
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $type = $request->query('type') === StockAdjustment::TYPE_DEDUCTION
            ? StockAdjustment::TYPE_DEDUCTION
            : StockAdjustment::TYPE_ADDITION;

        return Inertia::render('stock/adjustments/Create', [
            'type' => $type,
            'typeLabel' => StockAdjustment::TYPE_LABELS[$type],
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            // الكتالوج بالمتاح — إذن الخصم مابينفعش ينزّل المتاح تحت الصفر
            'products' => $this->inventory->catalogueWithStock()->all(),
            'reasons' => $this->reasonOptions(),
            'today' => now()->toDateString(),
            'breadcrumbs' => [
                ...$this->breadcrumbs(),
                ['title' => StockAdjustment::TYPE_LABELS[$type].' جديد', 'href' => route('stock-adjustments.create', ['type' => $type])],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $data = $request->validate([
            'type' => ['required', Rule::in([StockAdjustment::TYPE_ADDITION, StockAdjustment::TYPE_DEDUCTION])],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', Rule::in(array_keys(StockAdjustment::REASONS))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], [
            'reason.required' => 'اختار السبب.',
            'date.before_or_equal' => 'ماينفعش إذن بتاريخ في المستقبل.',
            'items.required' => 'لازم تضيف صنف واحد على الأقل للإذن.',
        ]);

        $adjustment = $this->documents->createAdjustment($data, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حفظ '.$adjustment->typeLabel().' '.$adjustment->number
                .' كمسودة — لازم يتعمد عشان المخزون يتحرّك.',
        ]);

        return redirect()->route('stock-adjustments.show', $adjustment);
    }

    public function show(StockAdjustment $stockAdjustment): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        return Inertia::render('stock/adjustments/Show', [
            'adjustment' => $this->present($stockAdjustment),
            'canApprove' => request()->user()?->allows(ErpAbility::ManageMasterData) ?? false,
            'breadcrumbs' => [
                ...$this->breadcrumbs(),
                ['title' => $stockAdjustment->number, 'href' => route('stock-adjustments.show', $stockAdjustment)],
            ],
        ]);
    }

    /** الاعتماد — ده اللي بيحرّك البضاعة، فللمدير والمحاسب */
    public function approve(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $this->documents->approveAdjustment($stockAdjustment, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم اعتماد '.$stockAdjustment->typeLabel().' '.$stockAdjustment->number
                .' وتعديل المخزون.',
        ]);

        return back();
    }

    public function print(StockAdjustment $stockAdjustment): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        return Inertia::render('print/StockAdjustment', [
            'company' => CompanyProfile::current()->present(),
            'adjustment' => $this->present($stockAdjustment),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(StockAdjustment $adjustment): array
    {
        return [
            'id' => $adjustment->id,
            'number' => $adjustment->number,
            'type' => $adjustment->type,
            'typeLabel' => $adjustment->typeLabel(),
            'date' => $adjustment->date->toDateString(),
            'warehouse' => $adjustment->warehouse?->name,
            'reason' => $adjustment->reasonLabel(),
            'status' => $adjustment->status,
            'isApproved' => $adjustment->isApproved(),
            'itemCount' => $adjustment->items->count(),
            'value' => $adjustment->value(),
            'createdBy' => $adjustment->user?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(StockAdjustment $adjustment): array
    {
        $adjustment->load([
            'items.product:id,sku,name,unit',
            'warehouse:id,name', 'user:id,name', 'approver:id,name',
            'stockCount:id,number',
        ]);

        return [
            ...$this->row($adjustment),
            'notes' => $adjustment->notes,
            'approvedBy' => $adjustment->approver?->name,
            'approvedAt' => $adjustment->approved_at?->toDateTimeString(),
            // الجرد اللي الإذن اتولّد منه، لو كان من جرد
            'stockCount' => $adjustment->stockCount ? [
                'id' => $adjustment->stockCount->id,
                'number' => $adjustment->stockCount->number,
            ] : null,
            'items' => $adjustment->items->map(fn (StockAdjustmentItem $item) => [
                'id' => $item->id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'quantity' => (float) $item->quantity,
                'unitCost' => (float) $item->unit_cost,
                'value' => $item->value(),
            ])->all(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function reasonOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(StockAdjustment::REASONS),
            array_values(StockAdjustment::REASONS),
        );
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function breadcrumbs(): array
    {
        return [
            ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
            ['title' => 'أذون المخزن', 'href' => route('stock-adjustments.index')],
        ];
    }
}
