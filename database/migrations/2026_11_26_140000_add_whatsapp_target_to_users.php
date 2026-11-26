<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «فتح واتساب على»: التطبيق ولا واتساب ويب.
 *
 * تفضيل شخصي بحت — اللي على الديسكتوب ومنزّلش التطبيق محتاج الويب،
 * واللي منزّله بيكره إن المتصفح يفتحله تبويب جديد كل مرة. عمود عادي
 * مش JSON: قيمة واحدة من اتنين، والاستعلام عليها أوضح.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_target', 10)->default(User::WHATSAPP_APP);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp_target');
        });
    }
};
