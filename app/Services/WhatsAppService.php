<?php

namespace App\Services;

use App\Models\User;
use App\Support\ArabicText;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * التعامل مع واتساب — إرسال وتعريف المستخدمين.
 *
 * القناة كلها مقفولة لحد ما `WHATSAPP_TOKEN` و`WHATSAPP_PHONE_NUMBER_ID`
 * يتحطوا في `.env`.
 */
class WhatsAppService
{
    public function enabled(): bool
    {
        return filled(config('notifications.whatsapp.token'))
            && filled(config('notifications.whatsapp.phone_number_id'));
    }

    /**
     * المستخدم المربوط برقم — المتأكّد منه بس.
     *
     * ده حارس البوابة كله: أي رقم مش مربوط بحساب متأكّد مابياخدش
     * بيانات، ولا حتى يعرف إن النظام موجود.
     */
    public function userFor(string $number): ?User
    {
        $digits = ArabicText::digits($number);

        if ($digits === '') {
            return null;
        }

        return User::query()
            ->whereNotNull('whatsapp_verified_at')
            ->where('whatsapp_number', $digits)
            ->first();
    }

    /**
     * رسالة نصية حرة.
     *
     * دي بتنفع بس جوه نافذة الـ 24 ساعة اللي بتفتح لما المستخدم يبعت
     * لنا — وده بالظبط حالة الرد على سؤال، فمفيش مشكلة هنا.
     */
    public function reply(string $to, string $text): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $version = config('notifications.whatsapp.api_version');
        $phoneId = config('notifications.whatsapp.phone_number_id');

        try {
            $response = Http::withToken(config('notifications.whatsapp.token'))
                ->timeout(15)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        // واتساب بيقصّ بعد 4096 حرف
                        'body' => mb_substr($text, 0, 4000),
                    ],
                ]);

            if ($response->failed()) {
                Log::error('WhatsApp reply failed', [
                    'status' => $response->status(),
                    'body' => $response->json('error.message') ?? $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp reply errored', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * كود تأكيد الرقم.
     *
     * الكود بيروح على الواتساب نفسه، فالتأكيد بيثبت إن الرقم شغّال
     * وإن اللي بيأكّده شايف رسايله.
     */
    public function sendVerificationCode(User $user, string $number, string $code): bool
    {
        return $this->reply(
            $number,
            "كود تأكيد رقمك في {$this->company()}: {$code}\n\n"
            ."اكتبه في صفحة الملف الشخصي عشان تربط الرقم بحسابك.\n"
            .'الكود صالح 10 دقايق.',
        );
    }

    private function company(): string
    {
        return (string) config('app.name');
    }
}
