<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * التصنيفات والموردين والتجار كان ناقصهم عمود التفعيل.
 *
 * المخازن والأصناف والمندوبين عندهم `is_active` من الأول، وباقي البيانات
 * الأساسية لازم تبقى زيهم: العميل بيوقّف المورد أو التاجر اللي وقف
 * التعامل معاه بدل ما يحذفه ويضيّع تاريخ فواتيره.
 *
 * الجداول مكتوبة واحد واحد بدل حلقة — التحليل الساكن (larastan) بيقرأ
 * الـ migrations عشان يعرف أعمدة الموديلات، وما بيعرفش يقرأ حلقة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
