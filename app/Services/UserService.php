<?php

namespace App\Services;

use App\Enums\ErpRole;
use App\Models\User;
use App\Services\Concerns\PreventsDeletingUsedRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * مستخدمو النظام وأدوارهم — شاشة للمدير لوحده.
 *
 * المستخدم الجديد بيتعمل مفعّل على طول (مفيش إيميل تفعيل): المدير هو اللي
 * بيسجّله ويسلّمه كلمة المرور بإيده، وفي نظام داخلي زي ده دورة التفعيل
 * بالإيميل بتوقف الشغل من غير فايدة.
 */
class UserService
{
    use PreventsDeletingUsedRecords;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        return User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->erpRole()?->value,
                'roleLabel' => $user->erpRole()?->label() ?? 'بدون دور',
                'createdAt' => $user->created_at?->toDateString(),
                'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User;

            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ])->save();

            $this->assign($user, $data['role']);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
            ])->save();

            if (filled($data['password'] ?? null)) {
                $user->forceFill(['password' => Hash::make($data['password'])])->save();
            }

            $this->assign($user, $data['role']);

            return $user;
        });
    }

    /**
     * إسناد الدور، وعمله لو لسه مش موجود.
     *
     * `ErpUserSeeder` بيعمل الأدوار الثلاثة، بس مابنعتمدش على ده: قاعدة
     * اتعملت بـ migrate من غير seed، أو دور اتمسح بالغلط، كان هيرمي
     * RoleDoesNotExist ويوقّع الشاشة.
     */
    private function assign(User $user, string $role): void
    {
        Role::findOrCreate($role, 'web');

        $user->syncRoles([$role]);
    }

    /**
     * @throws ValidationException
     */
    public function delete(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages([
                'user' => 'ما ينفعش تحذف حسابك انت.',
            ]);
        }

        if ($user->hasRole(ErpRole::Admin->value) && User::role(ErpRole::Admin->value)->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'ده آخر مدير في النظام — عيّن مدير تاني الأول.',
            ]);
        }

        $this->guardAgainstDeletion('user', $user->name, [
            'فاتورة متسجّلة باسمه' => $user->invoices()->count(),
            'سند متسجّل باسمه' => $user->payments()->count(),
        ]);

        DB::transaction(function () use ($user) {
            $user->teamMemberships()->delete();
            $user->delete();
        });
    }
}
