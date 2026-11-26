<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\Warehouse;
use App\Services\StockDocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * الجرد.
 *
 * الورقة بتتفتح على مخزن (وتصنيف اختياري) وبتتجمّد عليها أرصدة النظام،
 * المخزن بيعدّ ويكتب، والاعتماد بيولّد أذون الفروق لوحده.
 */
class StockCountController extends Controller
{
    public function __construct(private readonly StockDocumentService $documents) {}

    public function index(Request $request): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $filters = $request->only(['status', 'warehouse_id', 'search']);

        $query = StockCount::query()
            ->with(['warehouse:id,name', 'category:id,name', 'user:id,name'])
            ->withCount('items')
            ->when(filled($filters['status'] ?? null), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['search'] ?? null), fn (Builder $q) => $q->where('number', 'like', '%'.$filters['search'].'%'))
            ->latest('date')
            ->latest('id');

        return Inertia::render('stock/counts/Index', [
            'counts' => $query->paginate(20)->withQueryString()
                ->through(fn (StockCount $count) => [
                    'id' => $count->id,
                    'number' => $count->number,
                    'date' => $count->date->toDateString(),
                    'warehouse' => $count->warehouse?->name,
                    'category' => $count->category?->name,
                    'status' => $count->status,
                    'statusLabel' => $count->statusLabel(),
                    'itemCount' => $count->items_count,
                    'createdBy' => $count->user?->name,
                ]),
            'hasAny' => StockCount::query()->exists(),
            'filters' => $filters,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        return Inertia::render('stock/counts/Create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'today' => now()->toDateString(),
            'breadcrumbs' => [
                ...$this->breadcrumbs(),
                ['title' => 'جرد جديد', 'href' => route('stock-counts.create')],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = $this->documents->createCount($data, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم فتح ورقة الجرد '.$count->number.' بـ '.$count->items()->count().' صنف.',
        ]);

        return redirect()->route('stock-counts.show', $count);
    }

    public function show(StockCount $stockCount): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        return Inertia::render('stock/counts/Show', [
            'count' => $this->present($stockCount),
            'canApprove' => request()->user()?->allows(ErpAbility::ManageMasterData) ?? false,
            'breadcrumbs' => [
                ...$this->breadcrumbs(),
                ['title' => $stockCount->number, 'href' => route('stock-counts.show', $stockCount)],
            ],
        ]);
    }

    /** حفظ المعدود من غير اعتماد — الجرد بياخد يوم */
    public function saveCounted(Request $request, StockCount $stockCount): RedirectResponse
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.*.counted_quantity.min' => 'الكمية المعدودة ماينفعش تكون بالسالب.',
        ]);

        $this->documents->saveCounted($stockCount, $data['items']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حفظ الكميات المعدودة.',
        ]);

        return back();
    }

    /** الاعتماد — بيولّد أذون الفروق ويحرّك المخزون */
    public function approve(Request $request, StockCount $stockCount): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $result = $this->documents->approveCount($stockCount, $request->user());

        $documents = collect([$result['addition'], $result['deduction']])
            ->filter()
            ->map(fn ($adjustment) => $adjustment->number)
            ->implode(' و');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $documents === ''
                ? 'تم اعتماد الجرد — مفيش فروق.'
                : 'تم اعتماد الجرد وتولّد '.$documents.'.',
        ]);

        return back();
    }

    public function cancel(Request $request, StockCount $stockCount): RedirectResponse
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        $this->documents->cancelCount($stockCount, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إلغاء ورقة الجرد '.$stockCount->number.'.',
        ]);

        return back();
    }

    public function print(StockCount $stockCount): Response
    {
        Gate::authorize(ErpAbility::WriteStockDocuments->value);

        return Inertia::render('print/StockCount', [
            'company' => CompanyProfile::current()->present(),
            'count' => $this->present($stockCount),
            'printedAt' => now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(StockCount $count): array
    {
        $count->load([
            'items.product:id,sku,name,unit',
            'warehouse:id,name', 'category:id,name',
            'user:id,name', 'approver:id,name',
            'adjustments:id,number,type,stock_count_id',
        ]);

        return [
            'id' => $count->id,
            'number' => $count->number,
            'date' => $count->date->toDateString(),
            'warehouse' => $count->warehouse?->name,
            'category' => $count->category?->name,
            'status' => $count->status,
            'statusLabel' => $count->statusLabel(),
            'isEditable' => $count->isEditable(),
            'notes' => $count->notes,
            'createdBy' => $count->user?->name,
            'approvedBy' => $count->approver?->name,
            'approvedAt' => $count->approved_at?->toDateTimeString(),
            'items' => $count->items->map(fn (StockCountItem $item) => [
                'id' => $item->id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'systemQuantity' => (float) $item->system_quantity,
                'countedQuantity' => $item->counted_quantity === null
                    ? null
                    : (float) $item->counted_quantity,
                'difference' => $item->difference(),
            ])->all(),
            // الأذون اللي الاعتماد ولّدها
            'adjustments' => $count->adjustments->map(fn ($adjustment) => [
                'id' => $adjustment->id,
                'number' => $adjustment->number,
                'typeLabel' => $adjustment->typeLabel(),
            ])->all(),
        ];
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function breadcrumbs(): array
    {
        return [
            ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
            ['title' => 'الجرد', 'href' => route('stock-counts.index')],
        ];
    }
}
