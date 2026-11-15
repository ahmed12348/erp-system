<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * مستخدمو النظام الثلاثة بأدوارهم وفريق الشركة.
 *
 * السيدر ده بيشتغل على السيرفر بعد `composer install --no-dev`، فمفيش
 * استخدام لـ factory أو fake() — مكتبة Faker موجودة في require-dev بس
 * ومش هتكون متاحة هناك. كل حاجة بتتعمل بـ create() عادي.
 *
 * آمن لو اتشغّل أكتر من مرة: بيتخطّى أي مستخدم موجود بإيميله.
 */
class ErpUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@erp.test';

    public const COMPANY_NAME = 'شركة النيل للاستيراد والتوزيع';

    /** [الاسم, الإيميل, الدور] */
    private const USERS = [
        ['أحمد البطل', self::ADMIN_EMAIL, 'admin'],
        ['منى عبد الرحمن', 'accountant@erp.test', 'accountant'],
        ['كريم سعيد', 'sales@erp.test', 'sales'],
    ];

    public function run(): void
    {
        foreach (['admin', 'accountant', 'sales'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (self::USERS as [$name, $email, $role]) {
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $user = new User;

            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                // من غير التفعيل ده الـ middleware بتاع verified هيوقف الدخول
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ])->save();

            $user->assignRole($role);

            $this->attachCompanyTeam($user);
        }
    }

    /**
     * فريق الشركة — الـ starter kit بيربط المستخدم بفريق وبيعرض اسمه في
     * الشريط الجانبي، فبنعمله باسم الشركة بدل "'s Team" الإنجليزي.
     */
    private function attachCompanyTeam(User $user): void
    {
        $team = new Team;

        $team->forceFill([
            'name' => self::COMPANY_NAME,
            // بنحدّد الـ slug بإيدنا: الاسم عربي فـ Str::slug بيرجّعه فاضي،
            // وكمان الـ hook اللي بيولّده مابيشتغلش جوه WithoutModelEvents
            'slug' => $this->freeSlug('company'),
            'is_personal' => true,
        ])->save();

        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->switchTeam($team);
    }

    /** أول slug فاضي: company، company-2، company-3 … */
    private function freeSlug(string $base): string
    {
        $slug = $base;
        $suffix = 1;

        while (Team::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
