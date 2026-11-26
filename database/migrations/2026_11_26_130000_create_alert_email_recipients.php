<?php

use App\Models\AlertEmailRecipient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إيميلات بتستقبل تنبيهات المخزون من غير ما تكون مستخدمين.
     *
     * صاحب المحل مش بالضرورة بيفتح النظام كل يوم، وساعات عايز
     * محاسبه الخارجي أو شريكه يوصله التنبيه — من غير ما يبقى ليهم
     * حساب ولا صلاحيات.
     */
    public function up(): void
    {
        Schema::create('alert_email_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الإيميل الافتراضي — بيتزرع هنا عشان يشتغل على السيرفر على طول
        AlertEmailRecipient::query()->firstOrCreate(
            ['email' => 'ahmedayad944@yahoo.com'],
            ['name' => 'متابعة المخزون', 'is_active' => true],
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_email_recipients');
    }
};
