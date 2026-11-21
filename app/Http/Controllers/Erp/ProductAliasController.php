<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Services\AliasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * الأسماء البديلة للصنف.
 *
 * النظام بيتعلّمها لوحده من الفواتير الممسوحة، بس المستخدم محتاج
 * يشوفها ويقدر يضيف اسم بإيده أو يشيل واحد اتحفظ غلط — عشان مايفضلش
 * صنف بيتطابق باسم مش بتاعه.
 */
class ProductAliasController extends Controller
{
    public function __construct(private readonly AliasService $aliases) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $validated = $request->validate([
            'alias' => ['required', 'string', 'max:191'],
        ], [
            'alias.required' => 'اكتب الاسم البديل الأول.',
        ]);

        $alias = $this->aliases->rememberProduct(
            $product,
            $validated['alias'],
            $request->user(),
        );

        Inertia::flash('toast', $alias === null
            ? ['type' => 'error', 'message' => 'الاسم ده زي اسم الصنف بالظبط — مش محتاج اسم بديل.']
            : ['type' => 'success', 'message' => 'اتحفظ. الفواتير الجاية هتتطابق بالاسم ده.']);

        return back();
    }

    public function destroy(ProductAlias $alias): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $alias->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'اتشال الاسم البديل.',
        ]);

        return back();
    }
}
