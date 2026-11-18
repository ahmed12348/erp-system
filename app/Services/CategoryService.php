<?php

namespace App\Services;

use App\Models\Category;
use App\Services\Concerns\PreventsDeletingUsedRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * تصنيفات الأصناف.
 *
 * التصنيف مجرد تجميعة للأصناف في المخزون والتقارير — مالوش أي أثر محاسبي،
 * فالتعديل عليه آمن. الحذف بس هو اللي محتاج حراسة لأن الأصناف بتشاور عليه.
 */
class CategoryService
{
    use PreventsDeletingUsedRecords;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        return Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'isActive' => (bool) $category->is_active,
                'products' => $category->products_count,
            ]);
    }

    /**
     * التصنيفات الشغّالة للقوايم المنسدلة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function options(): Collection
    {
        return Category::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(fn () => Category::create([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update([
                'name' => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $category;
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(Category $category): void
    {
        $this->guardAgainstDeletion('category', $category->name, [
            'صنف مربوط بيه' => $category->products()->count(),
        ]);

        DB::transaction(fn () => $category->delete());
    }
}
