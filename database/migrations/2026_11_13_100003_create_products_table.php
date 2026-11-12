<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();                      // كود الصنف
            $table->string('name');
            $table->string('unit')->default('قطعة');              // قطعة / كرتونة / كيلو
            $table->decimal('purchase_price', 15, 2)->default(0); // آخر تكلفة شراء
            $table->decimal('sale_price', 15, 2)->default(0);     // سعر البيع للتاجر
            $table->decimal('wholesale_price', 15, 2)->nullable(); // سعر جملة الجملة
            $table->decimal('min_stock', 15, 3)->default(0);      // حد الطلب
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
