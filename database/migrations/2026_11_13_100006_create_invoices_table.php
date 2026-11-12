<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('type');          // sale | purchase | sale_return | purchase_return
            $table->string('number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('EGP');
            $table->decimal('exchange_rate', 15, 4)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid', 15, 2)->default(0);
            $table->string('status')->default('draft');           // draft | confirmed | cancelled
            $table->string('eta_status')->nullable();             // حالة الفاتورة الإلكترونية
            $table->string('eta_uuid')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
