<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * كل حسابات المخزون في مكان واحد.
 * الرصيد = مجموع stock_movements.quantity (موجب وارد، سالب منصرف).
 */
class InventoryService
{
    /** SQL لحساب رصيد الصنف — شغّال على MySQL و SQLite */
    private const BALANCE_SQL = '(select coalesce(sum(quantity), 0) from stock_movements where stock_movements.product_id = products.id)';

    /** عدد الأصناف اللي رصيدها نزل تحت حد الطلب */
    public function lowStockCount(): int
    {
        return $this->lowStockQuery()->count();
    }

    /**
     * الأصناف تحت حد الطلب، الأقل تغطية الأول.
     *
     * @return Collection<int, Product>
     */
    public function lowStockProducts(?int $limit = null): Collection
    {
        return $this->lowStockQuery()
            ->with('category')
            ->select('products.*')
            ->selectRaw(self::BALANCE_SQL.' as stock_balance')
            ->orderByRaw(self::BALANCE_SQL.' - products.min_stock asc')
            ->when($limit, fn (Builder $query) => $query->limit($limit))
            ->get();
    }

    /** قيمة المخزون بسعر التكلفة بالجنيه */
    public function inventoryValue(): float
    {
        return (float) DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->sum(DB::raw('stock_movements.quantity * products.purchase_price'));
    }

    /** إجمالي عدد الوحدات في كل المخازن */
    public function totalUnits(): float
    {
        return (float) DB::table('stock_movements')->sum('quantity');
    }

    /** رصيد صنف في مخزن معيّن (أو كل المخازن لو warehouseId = null) */
    public function stockFor(int $productId, ?int $warehouseId = null): float
    {
        return (float) DB::table('stock_movements')
            ->where('product_id', $productId)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->sum('quantity');
    }

    /**
     * كتالوج الأصناف مع الرصيد في كل مخزن — بيغذّي شاشة إنشاء الفاتورة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function catalogueWithStock(): Collection
    {
        $balances = DB::table('stock_movements')
            ->selectRaw('product_id, warehouse_id, sum(quantity) as qty')
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id');

        return Product::query()
            ->where('is_active', true)
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit,
                'category' => $product->category?->name,
                'salePrice' => (float) $product->sale_price,
                'wholesalePrice' => $product->wholesale_price ? (float) $product->wholesale_price : null,
                'purchasePrice' => (float) $product->purchase_price,
                'minStock' => (float) $product->min_stock,
                // الرصيد مفهرس بالمخزن عشان الواجهة تعرف المتاح فوراً
                'stock' => $balances->get($product->id, collect())
                    ->mapWithKeys(fn ($row) => [(string) $row->warehouse_id => (float) $row->qty]),
            ]);
    }

    /**
     * أرصدة الأصناف لكل مخزن — ده اللي بتعرضه شاشة المخزون.
     *
     * @param  array{search?: ?string, warehouse_id?: ?int, low?: bool|string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function stockRows(array $filters = []): Collection
    {
        $byProduct = DB::table('stock_movements')
            ->selectRaw('product_id, warehouse_id, sum(quantity) as qty, max(date) as last_move')
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id');

        $warehouses = DB::table('warehouses')->pluck('name', 'id');

        return Product::query()
            ->with('category:id,name')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($byProduct, $warehouses) {
                $rows = $byProduct->get($product->id, collect());

                $perWarehouse = $warehouses->map(fn ($name, $id) => [
                    'id' => $id,
                    'name' => $name,
                    'qty' => (float) ($rows->firstWhere('warehouse_id', $id)->qty ?? 0),
                ])->values();

                $total = (float) $rows->sum('qty');

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'category' => $product->category?->name,
                    'minStock' => (float) $product->min_stock,
                    'cost' => (float) $product->purchase_price,
                    'salePrice' => (float) $product->sale_price,
                    'total' => $total,
                    'value' => round($total * (float) $product->purchase_price, 2),
                    'isLow' => $total < (float) $product->min_stock,
                    'warehouses' => $perWarehouse,
                    'lastMove' => $rows->max('last_move'),
                ];
            })
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn (Collection $rows) => $rows->filter(
                    fn ($row) => collect($row['warehouses'])->firstWhere('id', (int) $filters['warehouse_id'])['qty'] != 0
                )
            )
            ->when(
                ($filters['low'] ?? null) === '1' || ($filters['low'] ?? null) === true,
                fn (Collection $rows) => $rows->filter(fn ($row) => $row['isLow'])
            )
            ->values();
    }

    /**
     * سجل حركة صنف — وارد ومنصرف مع رصيد جاري.
     *
     * @return array{product: array<string, mixed>, rows: list<array<string, mixed>>}
     */
    public function movementHistory(Product $product, ?int $warehouseId = null): array
    {
        $movements = $product->movements()
            ->with(['warehouse:id,name', 'reference'])
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $balance = 0.0;
        $rows = [];

        foreach ($movements as $movement) {
            $balance += (float) $movement->quantity;

            $rows[] = [
                'id' => $movement->id,
                'date' => $movement->date->toDateString(),
                'type' => $movement->type,
                'warehouse' => $movement->warehouse?->name,
                'quantity' => (float) $movement->quantity,
                'unitCost' => $movement->unit_cost ? (float) $movement->unit_cost : null,
                'notes' => $movement->notes,
                'balance' => round($balance, 3),
                'link' => $movement->reference instanceof Invoice
                    ? ($movement->reference->type === Invoice::TYPE_PURCHASE
                        ? "/purchases/{$movement->reference->id}"
                        : "/sales/{$movement->reference->id}")
                    : null,
                'reference' => $movement->reference?->number ?? null,
            ];
        }

        return [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit,
                'minStock' => (float) $product->min_stock,
                'cost' => (float) $product->purchase_price,
                'balance' => round($balance, 3),
            ],
            'rows' => array_reverse($rows),
        ];
    }

    /** @return Builder<Product> */
    private function lowStockQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereRaw(self::BALANCE_SQL.' < products.min_stock');
    }
}
