<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAlias;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\User;
use App\Support\ArabicText;
use Illuminate\Database\Eloquent\Model;

/**
 * ذاكرة أسماء الفواتير المكتوبة بخط اليد.
 *
 * العميل بيكتب «كبولى محوز» واسم الصنف عندنا «كبولي مجوز». أول مرة
 * المستخدم بيصحّحها بإيده؛ بنحفظ الاسم اللي كان على الورق، وبعد كده
 * المسح بيطابقه لوحده.
 *
 * الاسم البديل بيتخزّن مرة واحدة بس (`normalized` فريد)، فلو اتسند
 * لصنف تاني بعدين بنحدّث السطر الموجود بدل ما نعمل تاني — اسم واحد
 * ما ينفعش يودّي لصنفين.
 */
class AliasService
{
    /**
     * صنف بالاسم البديل.
     */
    public function matchProduct(?string $name): ?Product
    {
        $alias = $this->findProductAlias($name);

        return $alias?->product;
    }

    /**
     * تاجر بالاسم البديل.
     */
    public function matchCustomer(?string $name): ?Customer
    {
        $normalized = ArabicText::normalize($name);

        if ($normalized === '') {
            return null;
        }

        return CustomerAlias::with('customer')
            ->where('normalized', $normalized)
            ->first()?->customer;
    }

    public function findProductAlias(?string $name): ?ProductAlias
    {
        $normalized = ArabicText::normalize($name);

        if ($normalized === '') {
            return null;
        }

        return ProductAlias::with('product')
            ->where('normalized', $normalized)
            ->first();
    }

    /**
     * بيتعلّم اسم جديد لصنف.
     *
     * بيرجّع `null` لو الاسم فاضي أو هو نفسه اسم الصنف — مفيش فايدة
     * من اسم بديل مطابق للأصل.
     */
    public function rememberProduct(Product $product, ?string $alias, ?User $user = null): ?ProductAlias
    {
        return $this->remember(ProductAlias::class, 'product_id', $product, $alias, $user);
    }

    public function rememberCustomer(Customer $customer, ?string $alias, ?User $user = null): ?CustomerAlias
    {
        return $this->remember(CustomerAlias::class, 'customer_id', $customer, $alias, $user);
    }

    /**
     * @param  class-string<ProductAlias|CustomerAlias>  $aliasClass
     */
    private function remember(
        string $aliasClass,
        string $foreignKey,
        Model $owner,
        ?string $alias,
        ?User $user,
    ): ProductAlias|CustomerAlias|null {
        $alias = trim((string) $alias);
        $normalized = ArabicText::normalize($alias);

        if ($normalized === '' || $normalized === ArabicText::normalize($owner->name)) {
            return null;
        }

        /*
         * `updateOrCreate` على `normalized` — لو الاسم ده كان متسند
         * لصنف تاني، المستخدم بيصحّحه دلوقتي، فبيروح للجديد.
         */
        return $aliasClass::updateOrCreate(
            ['normalized' => $normalized],
            [
                $foreignKey => $owner->getKey(),
                'alias' => $alias,
                'user_id' => $user?->id,
            ],
        );
    }
}
