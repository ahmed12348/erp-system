<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * البيانات التجريبية الكاملة: 2 مخازن، 6 تصنيفات، 40 صنف، 5 موردين،
 * 25 تاجر، 4 مندوبين، و3 شهور حركة بيع وشراء وتحصيل ومرفقات.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * بيمسح بيانات النظام الأولى قبل ما يشتغل، عشان ينفع يتشغّل فوق النظام
 * النضيف (php artisan db:seed) من غير تعارض. المستخدمين ما بيتمسوش.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** الترتيب مش مهم — بنوقف فحص المفاتيح الأجنبية وقت المسح */
    private const TABLES = [
        'attachments',
        'stock_movements',
        'invoice_items',
        'payments',
        'invoices',
        'products',
        'categories',
        'customers',
        'suppliers',
        'sales_reps',
        'warehouses',
    ];

    public function run(): void
    {
        $this->truncateErpTables();

        $this->call([
            ErpUserSeeder::class,
            WarehouseSeeder::class,
            CategoryProductSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            TransactionSeeder::class,
            SalesRepSeeder::class,
            AttachmentSeeder::class,
        ]);
    }

    private function truncateErpTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
