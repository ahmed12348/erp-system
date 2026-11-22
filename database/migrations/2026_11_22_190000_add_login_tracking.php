<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تتبّع الدخول.
 *
 * `last_login_at` على المستخدم عشان السؤال الشائع «مين آخر مرة دخل
 * إمتى؟» يترد عليه من صف واحد من غير ما نمشّط سجل النشاط كله.
 *
 * وفهارس على سجل النشاط: الصفحة بتفلتر بالمستخدم والنوع والتاريخ،
 * وأدوات المساعد بتسأل بنفس الشكل، فمن غير الفهارس دي الاستعلام
 * بيمشّط الجدول كله — وهو الجدول اللي بيكبر أسرع من أي حاجة تانية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['causer_type', 'causer_id', 'created_at'], 'activity_causer_time_index');
            $table->index(['log_name', 'created_at'], 'activity_log_name_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_name_time_index');
            $table->dropIndex('activity_causer_time_index');
            $table->dropIndex(['created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
