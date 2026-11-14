<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المندوب بيترتبط بالتاجر، وكمان بالفاتورة والسند وقت تسجيلهم.
 * ليه الاتنين؟ لأن التاجر ممكن يتنقل لمندوب تاني بعدين، والعمولة
 * المفروض تفضل على المندوب اللي عمل البيعة وقتها فعلاً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('price_tier')
                ->constrained('sales_reps')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('user_id')
                ->constrained('sales_reps')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('user_id')
                ->constrained('sales_reps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_rep_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_rep_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_rep_id');
        });
    }
};
