<?php

namespace App\Concerns;

use App\Enums\ErpAbility;
use App\Enums\ErpRole;

/**
 * ربط المستخدم بدوره في النظام.
 *
 * الأدوار متخزّنة في spatie/permission، والـ trait ده بيحوّلها لـ enum عشان
 * باقي الكود يشتغل بأنواع بدل نصوص. مستخدم من غير دور معروف مالوش أي صلاحية.
 */
trait HasErpRole
{
    public function erpRole(): ?ErpRole
    {
        foreach (ErpRole::cases() as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function allows(ErpAbility $ability): bool
    {
        return $this->erpRole()?->allows($ability) ?? false;
    }

    /**
     * الصلاحيات في صورة مناسبة للواجهة — بتتبعت مع كل صفحة Inertia.
     *
     * @return array<string, bool>
     */
    public function abilityFlags(): array
    {
        return [
            'manageMasterData' => $this->allows(ErpAbility::ManageMasterData),
            'quickAddProduct' => $this->allows(ErpAbility::QuickAddProduct),
            'manageUsers' => $this->allows(ErpAbility::ManageUsers),
            'managePreparationOrders' => $this->allows(ErpAbility::ManagePreparationOrders),
            'writeStockDocuments' => $this->allows(ErpAbility::WriteStockDocuments),
        ];
    }
}
