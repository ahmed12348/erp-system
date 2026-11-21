<?php

namespace App\Services;

use App\Ai\ErpToolkit;
use App\Exceptions\AiUnavailableException;
use Illuminate\Support\Facades\Session;

/**
 * «اسأل السيستم» — محادثة قراءة فقط على بيانات النظام.
 *
 * المحادثة محفوظة في الجلسة بس: المستخدم يقفل الصفحة ويرجع يلاقيها،
 * ويعمل تسجيل خروج تتمسح. مفيش جدول ولا تنضيف دوري — وده كفاية
 * للمطلوب، والأسئلة ممكن تكون حساسة فمحلها مش قاعدة البيانات.
 */
class AskSystemService
{
    private const SESSION_KEY = 'ai.chat.messages';

    public function __construct(
        private readonly AiService $ai,
        private readonly ErpToolkit $toolkit,
    ) {}

    /**
     * بيضيف سؤال المستخدم، يسأل الموديل، ويحفظ الرد.
     *
     * لو المساعد وقع، سؤال المستخدم بيترجع من الجلسة عشان المحادثة
     * ماتفضلش فيها سؤال من غير رد.
     *
     * @return array<string, mixed>
     *
     * @throws AiUnavailableException
     */
    public function ask(string $question): array
    {
        $history = $this->history();
        $history[] = ['role' => 'user', 'content' => $question];

        try {
            $answer = $this->ai->chat(
                $this->systemPrompt(),
                $this->trimmed($history),
                $this->toolkit->tools(),
            );
        } catch (AiUnavailableException $e) {
            $this->store($this->trimmed($history));

            throw $e;
        }

        $history[] = ['role' => 'assistant', 'content' => $answer];

        $this->store($this->trimmed($history));

        return ['role' => 'assistant', 'content' => $answer];
    }

    /**
     * رسايل المحادثة الحالية.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function history(): array
    {
        /** @var array<int, array{role: string, content: string}> $messages */
        $messages = Session::get(self::SESSION_KEY, []);

        return $messages;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * تعليمات الموديل — مقصودة قصيرة.
     *
     * كل سطر هنا بيتبعت مع كل جولة أدوات، يعني بيتحاسب عليه مرتين
     * وتلاتة في السؤال الواحد. فبنسيب الشرح الطويل بتاع سيرفر الـ MCP
     * بره: وصف كل أداة والـ schema بتاعها بيتبعتوا مع الأدوات نفسها
     * وفيهم الكفاية، واللي فاضل هنا هو اللي الموديل مش هيعرفه منهم —
     * أسلوب الرد، وإزاي يقرا الأرقام، وإنه بيقرا بس.
     */
    public function systemPrompt(): string
    {
        return <<<'TXT'
            إنت مساعد جوه نظام ERP لشركة توزيع مصرية. بتقرا البيانات بالأدوات
            وبترد على الموظفين.

            الرد: عامية مصرية، جملتين أو تلاتة بالكتير، والقوايم نقط.
            ابدأ بالإجابة على طول من غير مقدمات.

            الأرقام: لاتينية دايمًا (0-9). الفلوس «12,500 ج.م» أو برمز العملة
            اللي الأداة رجّعته. التواريخ «13 نوفمبر 2026».

            المعاني: رصيد التاجر الموجب يعني عليه فلوس للشركة. المبالغ بالجنيه
            إلا لو الأداة كتبت العملة.

            ممنوع: تخمّن رقم أو تحسبه بنفسك — استعمل الأداة أو قول مش عارف.
            إنت بتقرا بس، فأي طلب تعديل أو إضافة رُدّ عليه إن المستخدم يعملها
            من الشاشة.
            TXT;
    }

    /**
     * آخر ن رسالة بس بتتبعت للموديل — بيحدّ تكلفة التوكنز في محادثة طويلة.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function trimmed(array $history): array
    {
        $limit = (int) config('ai.history_limit');

        return array_values(array_slice($history, -$limit));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function store(array $history): void
    {
        Session::put(self::SESSION_KEY, $history);
    }
}
