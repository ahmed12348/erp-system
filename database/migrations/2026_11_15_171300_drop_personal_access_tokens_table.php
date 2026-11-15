<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سيرفر الـ MCP بقى على Passport (OAuth + توكنات شخصية) بدل Sanctum،
 * فجدول Sanctum مابقاش ليه لازمة. الحذف آمن على السيرفرات اللي الجدول
 * اتعمل عليها قبل كده، وبيعدّي من غير حاجة على التنصيب الجديد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }
};
