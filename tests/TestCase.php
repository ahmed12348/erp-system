<?php

namespace Tests;

use App\Enums\ErpRole;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * مستخدم بدور معيّن.
     *
     * spatie/permission بيكاش الأدوار، والكاش بيفضل عايش بين الاختبارات في
     * نفس العملية — فبنفضّيه هنا عشان الدور اللي لسه اتعمل يتشاف.
     */
    protected function userWithRole(ErpRole $role): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return User::factory()->role($role)->create();
    }

    /** المدير — صلاحية كاملة على كل حاجة */
    protected function admin(): User
    {
        return $this->userWithRole(ErpRole::Admin);
    }

    /** المحاسب — بيدير البيانات الأساسية من غير المستخدمين */
    protected function accountant(): User
    {
        return $this->userWithRole(ErpRole::Accountant);
    }

    /** المندوب — بيشوف البيانات الأساسية بس */
    protected function salesUser(): User
    {
        return $this->userWithRole(ErpRole::Sales);
    }
}
