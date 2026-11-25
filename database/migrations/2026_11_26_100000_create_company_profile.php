<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * بيانات الشركة — صف واحد.
     *
     * جدول مش ملف إعدادات: الاستضافة المشتركة مفيهاش وصول لـ `.env` من
     * الشاشة، والعميل محتاج يعدّل الترويسة بنفسه من غير ما يبعتلنا.
     */
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('phones')->nullable();
            // الرقم الضريبي والسجل التجاري — مطلوبين على الفاتورة قانونًا
            $table->string('tax_number')->nullable();
            $table->string('commercial_registry')->nullable();
            $table->text('footer_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
