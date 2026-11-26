<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * شجرة تصنيفات المصروفات.
         *
         * `parent_id` بيعمل الشجرة، و`code` هو المفتاح اللي بيربطها
         * بدليل حسابات كامل بعدين: الأب بياخد كود زي 5100 والابن 5110،
         * فلما ييجي اليوم اللي النظام يمسك فيه قيود يومية، التصنيف
         * بيبقى معاه رقم حسابه جاهز بدل ما نرجع نعمل مطابقة بالاسم.
         */
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('expense_categories')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            /*
             * سند الصرف من الخزينة.
             *
             * المصروف بيعمل سند في `payments` عشان الخزينة تفضل مصدر
             * واحد للفلوس — مش جدولين بيتجمعوا على الشاشة.
             */
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('method');            // cash | bank
            $table->string('paid_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date', 'expense_category_id']);
        });

        // نوع السند الجديد محتاج يربط بالمصروف عشان الخزينة تعرف تسمّيه
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->after('invoice_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });

        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
