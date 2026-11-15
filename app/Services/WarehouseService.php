<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * إدارة المخازن.
 *
 * المخزن هو أول حاجة العميل بيسجّلها في النظام الفاضي — من غيره مش هيقدر
 * يعمل فاتورة شراء ولا بيع. الرصيد نفسه بيتحسب من حركات المخزون، فالمخزن
 * هنا مجرد وعاء بيتربط بيه الحركة.
 */
class WarehouseService
{
    /**
     * المخازن مع رصيدها وقيمتها — الأرقام من حركات المخزون مباشرة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $totals = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->selectRaw('stock_movements.warehouse_id,
                sum(stock_movements.quantity) as units,
                sum(stock_movements.quantity * products.purchase_price) as value,
                count(distinct stock_movements.product_id) as items')
            ->groupBy('stock_movements.warehouse_id')
            ->get()
            ->keyBy('warehouse_id');

        return Warehouse::query()
            ->withCount('invoices')
            ->orderBy('name')
            ->get()
            ->map(function (Warehouse $warehouse) use ($totals) {
                $row = $totals->get($warehouse->id);

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                    'isActive' => (bool) $warehouse->is_active,
                    'items' => (int) ($row->items ?? 0),
                    'units' => (float) ($row->units ?? 0),
                    'value' => round((float) ($row->value ?? 0), 2),
                    'invoices' => $warehouse->invoices_count,
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Warehouse
    {
        return DB::transaction(fn () => Warehouse::create([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $warehouse->update([
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $warehouse;
        });
    }

    /**
     * الحذف مسموح بس لو المخزن لسه مالوش أي حركة أو فواتير — غير كده
     * الأرصدة التاريخية هتبوظ. البديل إننا نوقّفه بدل ما نحذفه.
     *
     * @throws ValidationException
     */
    public function delete(Warehouse $warehouse): void
    {
        $movements = $warehouse->movements()->count();
        $invoices = $warehouse->invoices()->count();

        if ($movements > 0 || $invoices > 0) {
            throw ValidationException::withMessages([
                'warehouse' => sprintf(
                    'مش ممكن تحذف «%s» لأن عليه حركة مسجّلة (%s حركة مخزون و%s فاتورة). وقّفه بدل ما تحذفه.',
                    $warehouse->name,
                    number_format($movements),
                    number_format($invoices),
                ),
            ]);
        }

        DB::transaction(fn () => $warehouse->delete());
    }
}
