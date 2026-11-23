<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تفضيلات التنبيهات لكل مستخدم، واشتراكات الـ Web Push.
 *
 * التفضيلات عمود JSON واحد مش جدول بعمود لكل قناة: القنوات والأنواع
 * هيزيدوا (حد الائتمان، البيع تحت التكلفة، المتأخرات)، وكل إضافة
 * كانت هتبقى migration جديدة. الشكل `{نوع: {قناة: true}}`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('channels')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // الـ endpoint بيطول، فمش index كامل عليه
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->default('aesgcm');
            $table->timestamps();

            $table->index('user_id');
        });

        /*
         * بنفتكر إن الصنف نزل تحت الحد عشان ما نبعتش تنبيه مع كل بيعة.
         * التنبيه بيتبعت مرة واحدة عند النزول، وبيترجع تاني بس لما
         * الرصيد يطلع فوق الحد ويرجع ينزل.
         */
        Schema::create('stock_alert_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('below_minimum')->default(false);
            $table->boolean('out_of_stock')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alert_states');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('notification_settings');
    }
};
