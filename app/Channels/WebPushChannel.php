<?php

namespace App\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * إشعارات المتصفح — Web Push بمفاتيح VAPID.
 *
 * بتوصل حتى والنظام مقفول، وده اللي بيخلّيها مفيدة للنواقص: صاحب
 * المحل مش قاعد على الشاشة طول اليوم.
 *
 * الاشتراكات اللي المتصفح رفضها (404/410) بتتمسح لوحدها — المستخدم
 * شال الإذن أو مسح بيانات الموقع، ومفيش فايدة من المحاولة تاني.
 */
class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $public = config('notifications.push.public_key');
        $private = config('notifications.push.private_key');

        if (blank($public) || blank($private)) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $notifiable->getKey())->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('notifications.push.subject'),
                    'publicKey' => $public,
                    'privateKey' => $private,
                ],
            ]);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding,
                    ]),
                    json_encode($payload, JSON_UNESCAPED_UNICODE),
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                // المتصفح رفض الاشتراك نهائيًا — نشيله بدل ما نفضل نحاول
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();

                    continue;
                }

                Log::warning('Web push failed', [
                    'reason' => $report->getReason(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Web push errored', ['message' => $e->getMessage()]);
        }
    }
}
