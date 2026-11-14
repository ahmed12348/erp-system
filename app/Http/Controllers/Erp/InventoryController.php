<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'warehouse_id', 'low']);
        $rows = $this->inventory->stockRows($filters);

        return Inertia::render('inventory/Index', [
            'rows' => $rows,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'summary' => [
                'items' => $rows->count(),
                'units' => round((float) $rows->sum('total'), 3),
                'value' => round((float) $rows->sum('value'), 2),
                'low' => $rows->where('isLow', true)->count(),
            ],
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المخزون', 'href' => route('inventory.index')],
            ],
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        return Inertia::render('inventory/Show', [
            ...$this->inventory->movementHistory($product, $warehouseId),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'filters' => ['warehouse_id' => $warehouseId],
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المخزون', 'href' => route('inventory.index')],
                ['title' => $product->name, 'href' => route('inventory.show', $product)],
            ],
        ]);
    }
}
