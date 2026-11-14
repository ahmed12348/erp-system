<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * مدفوعات الموردين بتتسجّل بعملة المورد (USD / EUR / TRY)، فمن غير عمود
 * عملة مكانش ينفع نجمع الخزينة بالجنيه من غير ما نخلط عملات ببعض.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->default('EGP')->after('amount');
            $table->decimal('exchange_rate', 15, 4)->default(1)->after('currency');
        });

        // ترحيل السندات القديمة: التحصيلات بالجنيه، والمدفوعات بعملة المورد
        // وبسعر صرف الفاتورة المرتبطة. مكتوبة بالـ query builder مش SQL خام
        // عشان تشتغل على MySQL و SQLite (الاختبارات) مع بعض.
        $rates = DB::table('invoices')->pluck('exchange_rate', 'id');
        $currencies = DB::table('suppliers')->pluck('currency', 'id');

        DB::table('payments')
            ->where('type', 'disbursement')
            ->orderBy('id')
            ->chunkById(500, function ($payments) use ($rates, $currencies) {
                foreach ($payments as $payment) {
                    DB::table('payments')->where('id', $payment->id)->update([
                        'currency' => $currencies[$payment->supplier_id] ?? 'EGP',
                        'exchange_rate' => $payment->invoice_id
                            ? ($rates[$payment->invoice_id] ?? 1)
                            : 1,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);
        });
    }
};
