<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['name' => 'المخزن الرئيسي - العبور', 'location' => 'المنطقة الصناعية، مدينة العبور، القليوبية'],
            ['name' => 'مخزن الإسكندرية', 'location' => 'الورديان، الإسكندرية'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse + ['is_active' => true]);
        }
    }
}
