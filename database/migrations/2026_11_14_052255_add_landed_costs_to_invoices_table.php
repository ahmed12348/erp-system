<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مصاريف الاستيراد على فاتورة الشراء. دايماً بالجنيه المصري — لأنها
 * بتتدفع محلياً (شحن داخلي، جمارك، تخليص) حتى لو الفاتورة نفسها بالدولار.
 * بتتوزّع على البنود بالتناسب مع قيمة كل بند عشان تكلفة الصنف الواصلة تطلع صح.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('tax');
            $table->decimal('customs_cost', 15, 2)->default(0)->after('shipping_cost');
            $table->decimal('clearance_cost', 15, 2)->default(0)->after('customs_cost');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['shipping_cost', 'customs_cost', 'clearance_cost']);
        });
    }
};
