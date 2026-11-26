<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * الجرد — ورقة بتتعدّ عليها البضاعة.
         *
         * قبل `stock_adjustments` عشان الأخير بيشاور عليه.
         */
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            // التصنيف اختياري — جرد قسم واحد بدل المخزن كله
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->string('status')->default('open');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'warehouse_id']);
        });

        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            /*
             * رصيد النظام وقت فتح الورقة — بيتجمّد هنا.
             *
             * لو حسبناه وقت الاعتماد، أي بيعة حصلت والورقة مفتوحة بتبوّظ
             * الفرق: الورقة بتقول «عدّينا 950» والنظام بقى 980، فالفرق
             * بيطلع 30 بدل 50.
             */
            $table->decimal('system_quantity', 15, 3);
            $table->decimal('counted_quantity', 15, 3)->nullable();
            $table->timestamps();

            $table->index(['stock_count_id', 'product_id']);
        });

        /*
         * إذن الإضافة وإذن الخصم.
         *
         * بيتعملوا بالإيد، أو بيتولّدوا من الجرد وقت الاعتماد.
         */
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type');                 // addition | deduction
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            // الجرد اللي الإذن اتولّد منه — فاضي يعني إذن بالإيد
            $table->foreignId('stock_count_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('reason');
            $table->string('status')->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('stock_count_id');
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            // التكلفة بتتجمّد على الإذن — تقييم المخزون بيحسب بيها
            $table->decimal('unit_cost', 15, 2);
            $table->timestamps();

            $table->index(['stock_adjustment_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
    }
};
