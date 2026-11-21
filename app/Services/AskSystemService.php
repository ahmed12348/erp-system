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
     * تعليمات الموديل = قواعد قراءة الأرقام من سيرفر الـ MCP + أسلوب الرد.
     */
    public function systemPrompt(): string
    {
        $erpRules = $this->toolkit->instructions();

        return <<<TXT
            {$erpRules}

            إنت مساعد جوه نظام إدارة التوزيع نفسه، بتساعد موظفي الشركة وهما
            شغالين على الشاشات.

            أسلوب الرد:
            - رد بالعامية المصرية، قصير وعملي. جملتين أو تلاتة بالكتير.
            - لو الرد فيه أكتر من صنف أو تاجر، اعملهم نقط مش فقرة طويلة.
            - الأرقام دايمًا لاتينية (0-9) حتى في النص العربي.
            - الفلوس بالصيغة دي: 12,500 ج.م — والعملات التانية برمزها
              ($ و € و ₺) زي ما الأداة رجّعتها.
            - التواريخ بالصيغة دي: 13 نوفمبر 2026.
            - مفيش مقدمات زي «أكيد» أو «بص يا فندم» — ادخل في الإجابة على طول.

            قواعد مهمة:
            - لو السؤال محتاج رقم من النظام، استعمل الأدوات. ماتخمّنش ولا
              تحسب من عندك.
            - لو الأدوات رجّعت إنه مفيش بيانات، قول كده بوضوح بدل ما تخترع رقم.
            - لو السؤال مش ليه علاقة بالنظام، قول إنك بتساعد في بيانات النظام بس.
            - إنت بتقرا بس — لو حد طلب تضيف أو تعدّل أو تمسح أي حاجة، قوله
              إنك ما تقدرش وإنه يعملها من الشاشة بنفسه.
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
