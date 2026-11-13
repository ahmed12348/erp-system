<?php

namespace App\Services;

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

    /** @return Builder<Product> */
    private function lowStockQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereRaw(self::BALANCE_SQL.' < products.min_stock');
    }
}
