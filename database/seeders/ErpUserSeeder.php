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
            // الـ factory بينشئ الفريق الشخصي تلقائياً (starter kit)
            User::factory()
                ->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                ])
                ->assignRole($role);
        }
    }
}
