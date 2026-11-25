<?php

namespace App\Enums;

/**
 * أدوار النظام الثلاثة. الأسماء دي هي نفسها أسماء أدوار spatie/permission
 * اللي `ErpUserSeeder` بيعملها، فالدور بيتقرأ من المستخدم بـ `hasRole()`.
 */
enum ErpRole: string
{
    case Admin = 'admin';
    case Accountant = 'accountant';
    case Sales = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدير',
            self::Accountant => 'محاسب',
            self::Sales => 'مبيعات',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'صلاحية كاملة على كل حاجة، بما فيها المستخدمين والأدوار.',
            self::Accountant => 'يسجّل ويعدّل البيانات الأساسية والفواتير والسندات، من غير إدارة المستخدمين.',
            self::Sales => 'يشوف البيانات الأساسية ويعمل فواتير بيع — ما يعدّلش في البيانات الأساسية.',
        };
    }

    /**
     * صلاحيات الدور.
     *
     * @return array<ErpAbility>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Admin => ErpAbility::cases(),
            self::Accountant => [
                ErpAbility::ManageMasterData,
                ErpAbility::QuickAddProduct,
                ErpAbility::ManagePreparationOrders,
            ],
            // المندوب محتاج يضيف صنف وهو بيكتب فاتورة، بس مش بيدير كتالوج الأصناف
            self::Sales => [
                ErpAbility::QuickAddProduct,
                // بيفتح طلب التحضير وهو واقف مع التاجر — ومفيش أسعار فيه
                ErpAbility::ManagePreparationOrders,
            ],
        };
    }

    public function allows(ErpAbility $ability): bool
    {
        return in_array($ability, $this->abilities(), strict: true);
    }

    /**
     * الأدوار اللي ممكن تتسند لمستخدم من شاشة المستخدمين.
     *
     * @return array<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $role) => [
            'value' => $role->value,
            'label' => $role->label(),
            'description' => $role->description(),
        ], self::cases());
    }
}
