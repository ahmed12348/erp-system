<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouses) {}

    public function index(): Response
    {
        return Inertia::render('warehouses/Index', [
            'warehouses' => $this->warehouses->rows(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'المخزون', 'href' => route('inventory.index')],
                ['title' => 'المخازن', 'href' => route('warehouses.index')],
            ],
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $warehouse = $this->warehouses->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة مخزن «'.$warehouse->name.'».',
        ]);

        return back();
    }

    public function update(StoreWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->warehouses->update($warehouse, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تعديل بيانات المخزن.',
        ]);

        return back();
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $this->warehouses->delete($warehouse);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حذف المخزن.',
        ]);

        return back();
    }
}
