<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رقم واتساب المستخدم.
 *
 * بيتأكّد مرة واحدة بكود، و`whatsapp_verified_at` هي اللي بتسمح
 * بالإرسال والاستقبال. رقم مكتوب ومتأكّدش منه ممكن يكون غلط ويبعت
 * بيانات الشركة لحد تاني خالص.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // بالصيغة الدولية من غير + — زي ما واتساب بيرجّعه
            $table->string('whatsapp_number', 20)->nullable()->unique()->after('last_login_at');
            $table->timestamp('whatsapp_verified_at')->nullable()->after('whatsapp_number');
            $table->string('whatsapp_code', 10)->nullable()->after('whatsapp_verified_at');
            $table->timestamp('whatsapp_code_expires_at')->nullable()->after('whatsapp_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'whatsapp_verified_at',
                'whatsapp_code',
                'whatsapp_code_expires_at',
            ]);
        });
    }
};
