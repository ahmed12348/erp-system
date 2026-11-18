<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): Response
    {
        return Inertia::render('categories/Index', [
            'categories' => $this->categories->rows(),
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'التصنيفات', 'href' => route('categories.index')],
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->categories->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة تصنيف «'.$category->name.'».',
        ]);

        return back();
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تعديل بيانات التصنيف.',
        ]);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $this->categories->delete($category);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حذف التصنيف.',
        ]);

        return back();
    }
}
