<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Jobs\AnswerWhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * «اسأل السيستم» من على واتساب.
 *
 * نفس الوكلاء ونفس الصلاحيات بتاعة الشات في الموقع — الفرق إن السؤال
 * جاي من رسالة واتساب.
 *
 * **حارس البوابة**: الرقم لازم يكون مربوط بحساب مستخدم ومتأكّد منه.
 * أي رقم تاني مابياخدش رد خالص — مش رسالة رفض، ولا حتى تأكيد إن
 * الرقم ده بتاع نظام. حد بيجرّب أرقام عشوائية مالوش حق يعرف إن في
 * حاجة هنا أصلاً.
 *
 * الرد بيتعمل في الطابور: واتساب بيستنى 200 بسرعة وإلا بيعيد الإرسال،
 * والموديل بياخد ثواني.
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    /**
     * تأكيد الاشتراك — Meta بتنادي الرابط ده مرة واحدة بالـ GET.
     */
    public function verify(Request $request): Response
    {
        $token = config('notifications.whatsapp.verify_token');

        if (
            blank($token)
            || $request->query('hub_mode') !== 'subscribe'
            || $request->query('hub_verify_token') !== $token
        ) {
            return response('', 403);
        }

        // لازم نرجّع التحدي كنص خام زي ما هو
        return response((string) $request->query('hub_challenge'), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * الرسايل الواردة.
     *
     * بنرجّع 200 دايمًا — حتى لو الرقم مش معروف أو القناة مقفولة.
     * أي كود تاني بيخلّي Meta تعيد الإرسال وتفضل تعيد.
     */
    public function handle(Request $request): JsonResponse
    {
        if (! $this->whatsapp->enabled()) {
            return response()->json(['ok' => true]);
        }

        foreach ($this->messages($request) as $message) {
            $this->dispatchAnswer($message);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * بيطلّع الرسايل النصية من شكل Meta المتداخل.
     *
     * @return array<int, array{from: string, text: string}>
     */
    private function messages(Request $request): array
    {
        $messages = [];

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                foreach ((array) ($change['value']['messages'] ?? []) as $message) {
                    // النص بس — الصور والصوت مش مدعومين هنا
                    if (($message['type'] ?? null) !== 'text') {
                        continue;
                    }

                    $from = (string) ($message['from'] ?? '');
                    $text = trim((string) ($message['text']['body'] ?? ''));

                    if ($from === '' || $text === '') {
                        continue;
                    }

                    $messages[] = ['from' => $from, 'text' => $text];
                }
            }
        }

        return $messages;
    }

    /**
     * @param  array{from: string, text: string}  $message
     */
    private function dispatchAnswer(array $message): void
    {
        $user = $this->whatsapp->userFor($message['from']);

        if ($user === null) {
            /*
             * رقم مش مربوط بحساب. بنسجّل المحاولة للمدير وبنسكت —
             * مفيش رد، عشان ما نأكّدش لحد إن في نظام هنا.
             */
            Log::warning('WhatsApp message from an unlinked number', [
                'from' => substr($message['from'], 0, 6).'…',
            ]);

            return;
        }

        /*
         * حد استخدام زي الشات: نداء الموديل بيكلّف، ورسالة واتساب
         * أسهل بكتير في التكرار من فتح الشات.
         */
        $limiter = 'whatsapp:'.$user->id;

        if (RateLimiter::tooManyAttempts($limiter, (int) config('ai.rate_limits.chat'))) {
            $this->whatsapp->reply(
                $message['from'],
                'أسئلة كتير ورا بعض. استنى دقيقة وجرّب تاني.',
            );

            return;
        }

        RateLimiter::hit($limiter, 60);

        AnswerWhatsAppMessage::dispatch($user->id, $message['from'], $message['text']);
    }
}
