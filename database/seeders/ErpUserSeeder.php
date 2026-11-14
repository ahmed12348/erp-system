<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * مستخدمو العرض التوضيحي + الأدوار.
 */
class ErpUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@erp.test';

    public const COMPANY_NAME = 'شركة النيل للاستيراد والتوزيع';

    public function run(): void
    {
        foreach (['admin', 'accountant', 'sales'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $users = [
            ['أحمد البطل', self::ADMIN_EMAIL, 'admin'],
            ['منى عبد الرحمن', 'accountant@erp.test', 'accountant'],
            ['كريم سعيد', 'sales@erp.test', 'sales'],
        ];

        foreach ($users as [$name, $email, $role]) {
            // لو السيدر اتشغّل قبل كده (مثلاً db:seed بعدين DemoSeeder) ما نكرّرش
            if (User::where('email', $email)->exists()) {
                continue;
            }

            // الـ factory بينشئ الفريق الشخصي تلقائياً (starter kit) باسم إنجليزي،
            // فبنسميه باسم الشركة عشان ما يظهرش "'s Team" في الواجهة العربية
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
            ]);

            $user->assignRole($role);
            $user->currentTeam?->update(['name' => self::COMPANY_NAME]);
        }
    }
}
