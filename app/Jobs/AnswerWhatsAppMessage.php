<?php

namespace App\Jobs;

use App\Ai\AgentRegistry;
use App\Ai\Agents\Agent;
use App\Exceptions\AiUnavailableException;
use App\Models\User;
use App\Services\AiService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * بيرد على سؤال جه من واتساب.
 *
 * في الطابور لأن واتساب بيستنى 200 في ثواني وإلا بيعيد الإرسال،
 * والموديل بياخد وقته.
 *
 * **الصلاحيات زي الموقع بالظبط**: الوكلاء المتاحين للمستخدم بس،
 * وأدواتهم بس. مندوب المبيعات مش هياخد من على واتساب حاجة مش شايفها
 * على الشاشة.
 *
 * مفيش تاريخ محادثة هنا: كل رسالة سؤال مستقل. تاريخ الشات محفوظ في
 * جلسة المتصفح، وواتساب مالوش جلسة — وسؤال متابعة من غير سياق أحسن
 * من سياق غلط من ساعتين.
 */
class AnswerWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    /** مهلة أقصر من مهلة الشات — المستخدم مستني على موبايله */
    public int $timeout = 30;

    public int $tries = 1;

    public function __construct(
        private readonly int $userId,
        private readonly string $from,
        private readonly string $question,
    ) {}

    public function handle(
        AiService $ai,
        AgentRegistry $agents,
        WhatsAppService $whatsapp,
    ): void {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        // نفس الوكيل الافتراضي بتاع الشات — أول تبويب مسموح للدور ده
        $agent = $agents->resolve($user, null);

        if ($agent === null) {
            $whatsapp->reply($this->from, 'مفيش صلاحية لحسابك تسأل من هنا.');

            return;
        }

        try {
            $answer = $ai->chat(
                $this->prompt($agent),
                [['role' => 'user', 'content' => $this->question]],
                $agents->toolsFor($agent),
            );
        } catch (AiUnavailableException $e) {
            $whatsapp->reply($this->from, $e->arabicMessage);

            return;
        }

        $whatsapp->reply($this->from, $answer);
    }

    /**
     * تعليمات الرد على واتساب.
     *
     * أقصر من رد الشاشة: الرسالة بتتقرا على موبايل، والجداول العريضة
     * بتتكسر فيها. فالرد بيبقى جملة ونقط قليلة.
     */
    private function prompt(Agent $agent): string
    {
        $specialism = $agent->instructions();

        return <<<TXT
            إنت مساعد نظام ERP لشركة توزيع مصرية، بترد على رسالة واتساب.

            {$specialism}

            الرد على واتساب: **قصير جدًا** — جملة أو جملتين، وأقصى حاجة
            خمس نقط. الرسالة بتتقرا على موبايل، فمفيش جداول عريضة ولا
            فقرات طويلة. لو النتيجة طويلة، قول أهم تلات حاجات وقول
            إن التفاصيل الكاملة على النظام.

            عامية مصرية. أرقام لاتينية. الفلوس «12,500 ج.م».

            **القاعدة الذهبية**: كل رقم بتقوله لازم يكون جاي من أداة زي
            ما هي رجّعته. ماتجمعش ولا تحسب نسبة من عندك — لو الرقم مش
            في رد الأداة، قول إنه مش متاح.

            إنت بتقرا بس. أي طلب تعديل رُدّ عليه إن المستخدم يعمله من
            النظام.
            TXT;
    }
}
