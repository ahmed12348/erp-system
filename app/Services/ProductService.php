<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Concerns\PreventsDeletingUsedRecords;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * كتالوج الأصناف — الاسم والكود والوحدة والأسعار وحد الطلب.
 *
 * الفرق بين الصفحة دي وصفحة المخزون: هنا بنعرّف الصنف نفسه، وهناك بنتابع
 * رصيده. تكلفة الشراء مابتتكتبش بالإيد غير أول مرة — بعد كده فاتورة الشراء
 * هي اللي بتحدّثها بالتكلفة الواصلة.
 */
class ProductService
{
    use PreventsDeletingUsedRecords;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Product::query()
            ->with('category:id,name')
            ->withSum('movements as stock', 'quantity')
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('name', 'like', $term)->orWhere('sku', 'like', $term);
                })
            )
            ->when(
                filled($filters['category_id'] ?? null),
                fn (Builder $query) => $query->where('category_id', $filters['category_id'])
            )
            ->when(
                ($filters['status'] ?? null) === 'active',
                fn (Builder $query) => $query->where('is_active', true)
            )
            ->when(
                ($filters['status'] ?? null) === 'inactive',
                fn (Builder $query) => $query->where('is_active', false)
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->present($product));
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit' => $product->unit,
            'categoryId' => $product->category_id,
            'category' => $product->category?->name,
            'purchasePrice' => (float) $product->purchase_price,
            'salePrice' => (float) $product->sale_price,
            'wholesalePrice' => $product->wholesale_price === null ? null : (float) $product->wholesale_price,
            'minStock' => (float) $product->min_stock,
            'isActive' => (bool) $product->is_active,
            'stock' => (float) ($product->stock ?? 0),
        ];
    }

    /**
     * كود مقترح للصنف الجديد: P-0001، P-0002 … المستخدم يقدر يغيّره.
     */
    public function suggestSku(): string
    {
        $used = Product::query()
            ->where('sku', 'like', 'P-%')
            ->pluck('sku')
            ->map(fn (string $sku) => (int) substr($sku, 2))
            ->filter(fn (int $number) => $number > 0);

        return 'P-'.str_pad((string) (($used->max() ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(fn () => Product::create($this->attributes($data)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($this->attributes($data));

            return $product;
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(Product $product): void
    {
        $this->guardAgainstDeletion('product', $product->name, [
            'حركة مخزون' => $product->movements()->count(),
            'بند فاتورة' => $product->invoiceItems()->count(),
        ]);

        DB::transaction(fn () => $product->delete());
    }

    /**
     * الأصناف الشغّالة لقوايم الفواتير.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function options(): Collection
    {
        return Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'purchase_price'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit,
                'lastCost' => (float) $product->purchase_price,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'category_id' => $data['category_id'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? 0,
            // السعر الفاضي معناه إن التاجر الجملة بياخد سعر البيع العادي
            'wholesale_price' => ($data['wholesale_price'] ?? null) === '' ? null : ($data['wholesale_price'] ?? null),
            'min_stock' => $data['min_stock'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ];
    }
}
