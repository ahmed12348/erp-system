<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نتيجة المسح على الفاتورة المسودة.
 *
 * `invoice_items.product_id` مطلوب (NOT NULL)، والمسودة بطبيعتها ممكن
 * يكون فيها سطور لسه ما اتطابقتش بصنف. فبدل ما نضعّف الجدول عشان حالة
 * مؤقتة، المسودة بتمسك نتيجة المسح كاملة هنا — السطور بنصّها الأصلي
 * ودرجة ثقتها — وبنولّد `invoice_items` الحقيقية وقت التأكيد بس.
 *
 * الفاتورة المؤكّدة عمرها ما بتقرا من العمود ده.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('scan_payload')->nullable()->after('notes');
            $table->timestamp('scanned_at')->nullable()->after('scan_payload');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['scan_payload', 'scanned_at']);
        });
    }
};
