<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * قناة واتساب — Meta Cloud API الرسمي.
 *
 * **قوالب مش رسايل حرة**: واتساب مابيسمحش تبعت رسالة حرة لحد مابدأش
 * محادثة معاك في آخر 24 ساعة، والتنبيهات بطبعها بتيجي من ناحيتنا
 * إحنا. فكل تنبيه بيتبعت كقالب معتمد من Meta بمتغيّراته.
 *
 * مقفولة لحد ما `WHATSAPP_TOKEN` و`WHATSAPP_PHONE_NUMBER_ID` يتحطوا
 * في `.env`. من غيرهم بتسكت وخلاص — ما بتوقّعش التنبيه كله.
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = $this->number($notifiable);
        $token = config('notifications.whatsapp.token');
        $phoneId = config('notifications.whatsapp.phone_number_id');

        if ($to === null || blank($token) || blank($phoneId)) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        $version = config('notifications.whatsapp.api_version');

        try {
            $response = Http::withToken($token)
                // مهلة قصيرة — التنبيه مش يستاهل يعطّل الطابور
                ->timeout(15)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $message['template'],
                        'language' => ['code' => config('notifications.whatsapp.language')],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => array_map(
                                fn (string $value): array => ['type' => 'text', 'text' => $value],
                                $message['parameters'] ?? [],
                            ),
                        ]],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('WhatsApp notification failed', [
                    'status' => $response->status(),
                    'body' => $response->json('error.message') ?? $response->body(),
                    'template' => $message['template'],
                ]);
            }
        } catch (\Throwable $e) {
            // التنبيه مش يستاهل إنه يوقّع الطابور
            Log::error('WhatsApp notification errored', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * رقم الواتساب المتحقّق منه بس.
     *
     * رقم مكتوب ومتأكّدش منه ممكن يكون غلط ويروح لحد تاني خالص.
     */
    private function number(object $notifiable): ?string
    {
        if (! method_exists($notifiable, 'routeNotificationForWhatsApp')) {
            return null;
        }

        return $notifiable->routeNotificationForWhatsApp();
    }
}
