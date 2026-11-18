<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuickStoreProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category_id', 'status']);

        return Inertia::render('products/Index', [
            'products' => $this->products->paginate($filters),
            'categories' => $this->categories->options(),
            'units' => Product::UNITS,
            'suggestedSku' => $this->products->suggestSku(),
            // بيفرّق بين «لسه مافيش أصناف» و«البحث مش جايب نتيجة»
            'hasAny' => Product::query()->exists(),
            'filters' => $filters,
            'breadcrumbs' => [
                ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
                ['title' => 'الأصناف', 'href' => route('products.index')],
            ],
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة صنف «'.$product->name.'».',
        ]);

        return back();
    }

    /**
     * إضافة صنف من جوه فورم الفاتورة.
     *
     * بيرجّع للصفحة اللي جاي منها عشان الفاتورة اللي المستخدم بيكتبها
     * ما تضيعش — Inertia بيعيد تحميل الـ props فالصنف الجديد بيظهر في
     * القايمة على طول.
     */
    public function quickStore(QuickStoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم إضافة صنف «'.$product->name.'» — اختاره من القايمة.',
        ]);

        return back();
    }

    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تعديل بيانات الصنف.',
        ]);

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $this->products->delete($product);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم حذف الصنف.',
        ]);

        return back();
    }
}
