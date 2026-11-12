<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('type');                    // in | out | adjust | transfer
            $table->decimal('quantity', 15, 3);        // موجب = داخل، سالب = خارج
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->nullableMorphs('reference');       // الفاتورة أو التسوية اللي عملت الحركة
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
