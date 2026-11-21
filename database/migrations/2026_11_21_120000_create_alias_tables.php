<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أسماء بديلة للأصناف والتجار.
 *
 * الفواتير اللي بتتمسح مكتوبة بخط اليد، والاسم على الورق نادرًا ما
 * بيطابق الاسم في النظام: «كبولي مجوز» في الكتالوج ممكن تتكتب «كبولى
 * محوز» أو «كبولي ٢ جوز». أول ما المستخدم يصحّح المطابقة مرة واحدة،
 * بنحفظ الاسم اللي كان على الورق هنا، وفي المرة الجاية بيتطابق لوحده.
 *
 * ده أهم جزء في تحسين المسح: النظام بيتعلّم خط العميل مع الوقت بدل ما
 * المستخدم يصحّح نفس الصنف كل أسبوع.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // الاسم زي ما اتكتب على الورق — بيتعرض للمستخدم
            $table->string('alias');
            /*
             * نسخة مطبّعة للمقارنة (من غير همزات ولا تشكيل ولا أرقام
             * هندية). فريدة عشان اسم واحد ما يوديش لصنفين.
             */
            $table->string('normalized')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::create('customer_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('normalized')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_aliases');
        Schema::dropIfExists('product_aliases');
    }
};
