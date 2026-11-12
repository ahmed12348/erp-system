<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ErpUserSeeder::class,
            WarehouseSeeder::class,
            CategoryProductSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
