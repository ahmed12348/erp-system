<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PreparationOrder;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * كل حسابات المخزون في مكان واحد.
 * الرصيد = مجموع stock_movements.quantity (موجب وارد، سالب منصرف).
 *
 * **الرصيد مش هو المتاح.** طلب التحضير المفتوح أو المحضّر بيحجز كميات
 * لسه ما اتخصمتش من المخزن — البضاعة لسه على الرف بس محدش ينفع يبيعها.
 * فالمتاح = الرصيد ناقص المحجوز، وده اللي بيتعرض في منتقي الأصناف
 * وشاشة المخزون وفاتورة البيع، وده اللي الفاتورة بتتفحص عليه. من غير
 * كده بضاعة محجوزة لتاجر بتتباع لتاجر تاني والمخزن بيلاقي نفسه ناقص.
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
     * الكمية المحجوزة بطلبات تحضير مفتوحة أو محضّرة.
     *
     * `$exceptOrderId` بيستثني طلب بعينه — بيتستعمل وقت تحويل الطلب
     * لفاتورة: الفاتورة مالهاش تتمنع بحجز الطلب اللي هي نفسها بتنفّذه.
     */
    public function reservedFor(int $productId, ?int $warehouseId = null, ?int $exceptOrderId = null): float
    {
        return (float) $this->reservationQuery($exceptOrderId)
            ->where('preparation_order_items.product_id', $productId)
            ->when($warehouseId, fn ($query) => $query->where('preparation_orders.warehouse_id', $warehouseId))
            ->sum(DB::raw('coalesce(preparation_order_items.prepared_quantity, preparation_order_items.quantity)'));
    }

    /**
     * المتاح للبيع = الرصيد ناقص المحجوز.
     *
     * ممكن يرجع بالسالب نظريًا لو حد عدّل حركات المخزون من برّه
     * النظام؛ بنقصّه عند الصفر عشان الواجهة ما تعرضش رقم سالب
     * مالوش معنى.
     */
    public function availableFor(int $productId, ?int $warehouseId = null, ?int $exceptOrderId = null): float
    {
        $available = $this->stockFor($productId, $warehouseId)
            - $this->reservedFor($productId, $warehouseId, $exceptOrderId);

        return round(max($available, 0), 3);
    }

    /**
     * المحجوز لكل (صنف، مخزن) — استعلام واحد لكل الشاشة.
     *
     * الشاشات بتعرض عشرات الأصناف؛ نداء `reservedFor` لكل صنف معناه
     * استعلام لكل سطر.
     *
     * @return Collection<string, float> مفهرسة بـ "productId-warehouseId"
     */
    public function reservations(?int $exceptOrderId = null): Collection
    {
        return $this->reservationQuery($exceptOrderId)
            ->selectRaw(
                'preparation_order_items.product_id as product_id,'
                .' preparation_orders.warehouse_id as warehouse_id,'
                .' sum(coalesce(preparation_order_items.prepared_quantity, preparation_order_items.quantity)) as qty'
            )
            ->groupBy('preparation_order_items.product_id', 'preparation_orders.warehouse_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->product_id.'-'.$row->warehouse_id => (float) $row->qty,
            ]);
    }

    /**
     * كتالوج الأصناف مع المتاح في كل مخزن — بيغذّي شاشة إنشاء الفاتورة
     * وشاشة طلب التحضير.
     *
     * `stock` هنا **المتاح** مش الرصيد الخام: الرصيد ناقص المحجوز
     * بطلبات التحضير. الفورم بيمنع الكمية تعدّي الرقم ده، فلازم يكون
     * هو نفسه اللي الحفظ بيتفحص عليه — وإلا الفورم يقول «تمام» والحفظ
     * يرفض.
     *
     * `$exceptOrderId` بيستثني حجز طلب بعينه — وقت تعديل أو تحويل
     * الطلب نفسه.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function catalogueWithStock(?int $exceptOrderId = null): Collection
    {
        $balances = DB::table('stock_movements')
            ->selectRaw('product_id, warehouse_id, sum(quantity) as qty')
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id');

        $reserved = $this->reservations($exceptOrderId);

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
                // المتاح مفهرس بالمخزن عشان الواجهة تعرفه فوراً من غير نداء
                'stock' => $balances->get($product->id, collect())
                    ->mapWithKeys(fn ($row) => [
                        (string) $row->warehouse_id => round(max(
                            (float) $row->qty - $reserved->get($product->id.'-'.$row->warehouse_id, 0.0),
                            0,
                        ), 3),
                    ]),
                // المحجوز لوحده — الواجهة بتقوله للمستخدم عشان يفهم الفرق
                'reserved' => $balances->get($product->id, collect())
                    ->mapWithKeys(fn ($row) => [
                        (string) $row->warehouse_id => $reserved->get($product->id.'-'.$row->warehouse_id, 0.0),
                    ]),
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
        $reserved = $this->reservations();

        return Product::query()
            ->with('category:id,name')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($byProduct, $warehouses, $reserved) {
                $rows = $byProduct->get($product->id, collect());

                $perWarehouse = $warehouses->map(function ($name, $id) use ($rows, $product, $reserved) {
                    $qty = (float) ($rows->firstWhere('warehouse_id', $id)->qty ?? 0);
                    $held = $reserved->get($product->id.'-'.$id, 0.0);

                    return [
                        'id' => $id,
                        'name' => $name,
                        'qty' => $qty,
                        'reserved' => $held,
                        'available' => round(max($qty - $held, 0), 3),
                    ];
                })->values();

                $total = (float) $rows->sum('qty');
                $held = (float) collect($perWarehouse)->sum('reserved');

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
                    // المحجوز بطلبات التحضير، والمتاح للبيع بعده
                    'reserved' => $held,
                    'available' => round(max($total - $held, 0), 3),
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
                // الفاتورة والمرتجع وإذن الإضافة/الخصم — كلهم بيفتحوا من هنا
                'link' => $this->movementLink($movement),
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

    /**
     * رابط المستند اللي عمل الحركة.
     *
     * الحركة ممكن تكون من فاتورة أو مرتجع أو إذن مخزني — واللي بيبص
     * على سجل الصنف عايز يفتح الورقة اللي حرّكته، أيًا كانت.
     */
    private function movementLink(StockMovement $movement): ?string
    {
        $reference = $movement->reference;

        if ($reference instanceof StockAdjustment) {
            return "/stock-adjustments/{$reference->id}";
        }

        if (! $reference instanceof Invoice) {
            return null;
        }

        return match ($reference->type) {
            Invoice::TYPE_PURCHASE => "/purchases/{$reference->id}",
            Invoice::TYPE_SALE_RETURN, Invoice::TYPE_PURCHASE_RETURN => "/returns/{$reference->id}",
            default => "/sales/{$reference->id}",
        };
    }

    /** أساس استعلام الحجز — بنود طلبات لسه حاجزة */
    private function reservationQuery(?int $exceptOrderId = null): \Illuminate\Database\Query\Builder
    {
        return DB::table('preparation_order_items')
            ->join(
                'preparation_orders',
                'preparation_orders.id',
                '=',
                'preparation_order_items.preparation_order_id'
            )
            ->whereIn('preparation_orders.status', PreparationOrder::RESERVING)
            ->when($exceptOrderId, fn ($query) => $query->where('preparation_orders.id', '!=', $exceptOrderId));
    }

    /** @return Builder<Product> */
    private function lowStockQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereRaw(self::BALANCE_SQL.' < products.min_stock');
    }
}
