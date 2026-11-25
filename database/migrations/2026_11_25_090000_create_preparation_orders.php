<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparation_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->string('status')->default('open');
            /*
             * الفاتورة اللي الطلب اتحوّل لها. بـ `nullOnDelete` عشان حذف
             * فاتورة ما يحذفش الطلب — الطلب سجل قايم بذاته.
             */
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            /*
             * حساب «المتاح» بيمرّ على كل الطلبات المفتوحة أو المحضّرة في
             * مخزن معيّن، وده بيحصل في كل فتحة لشاشة مخزون أو فاتورة
             * بيع — فالفهرس ده مش رفاهية.
             */
            $table->index(['status', 'warehouse_id']);
        });

        Schema::create('preparation_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preparation_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // الكمية المطلوبة وقت فتح الطلب
            $table->decimal('quantity', 15, 3);
            /*
             * اللي المخزن حضّره فعلاً. فاضي معناه «لسه زي المطلوب».
             * بيتفضل عمود لوحده مش تعديل على `quantity` عشان يفضل
             * واضح إن التاجر طلب كام والمخزن طلّع كام — ده أول سؤال
             * بيتسأل لما حد يختلف على كمية.
             */
            $table->decimal('prepared_quantity', 15, 3)->nullable();
            $table->timestamps();

            $table->index(['preparation_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preparation_order_items');
        Schema::dropIfExists('preparation_orders');
    }
};
