<?php

namespace App\Services;

use App\Ai\AgentRegistry;
use App\Ai\Agents\Agent;
use App\Exceptions\AiUnavailableException;
use Illuminate\Support\Facades\Session;

/**
 * «اسأل السيستم» — محادثة قراءة فقط على بيانات النظام.
 *
 * المحادثة متقسّمة على وكلاء (تبويبات): كل واحد ليه تعليماته وأدواته
 * وتاريخه المستقل. التقسيم مش شكلي — الموديل بياخد وصف كل أداة مع كل
 * نداء، فمجموعة صغيرة مركّزة أسرع وأدق من عشرين أداة في سلّة واحدة.
 *
 * التاريخ محفوظ في الجلسة بس: المستخدم يقفل الصفحة ويرجع يلاقيه،
 * ويعمل تسجيل خروج يتمسح.
 */
class AskSystemService
{
    private const SESSION_KEY = 'ai.chat';

    public function __construct(
        private readonly AiService $ai,
        private readonly AgentRegistry $agents,
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
    public function ask(Agent $agent, string $question): array
    {
        $history = $this->history($agent);
        $history[] = ['role' => 'user', 'content' => $question];

        try {
            $answer = $this->ai->chat(
                $this->systemPrompt($agent),
                $this->trimmed($history),
                $this->agents->toolsFor($agent),
            );
        } catch (AiUnavailableException $e) {
            $this->store($agent, $this->trimmed($history));

            throw $e;
        }

        $history[] = ['role' => 'assistant', 'content' => $answer];

        $this->store($agent, $this->trimmed($history));

        return ['role' => 'assistant', 'content' => $answer];
    }

    /**
     * رسايل تبويب واحد.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function history(Agent $agent): array
    {
        /** @var array<int, array{role: string, content: string}> $messages */
        $messages = Session::get($this->key($agent), []);

        return $messages;
    }

    public function clear(Agent $agent): void
    {
        Session::forget($this->key($agent));
    }

    /**
     * تعليمات الموديل — القواعد العامة + تخصص الوكيل.
     *
     * مقصودة قصيرة: كل سطر بيتبعت مع كل جولة أدوات، يعني بيتحاسب عليه
     * مرتين وتلاتة في السؤال الواحد. وصف كل أداة بيتبعت مع الأداة
     * نفسها، فمالوش لازمة يتكرّر هنا.
     */
    public function systemPrompt(Agent $agent): string
    {
        $screens = implode('، ', self::SCREENS);
        $specialism = $agent->instructions();

        return <<<TXT
            إنت مساعد جوه نظام ERP لشركة توزيع مصرية. بتقرا البيانات بالأدوات
            وبترد على الموظفين.

            {$specialism}

            الرد: عامية مصرية، جملتين أو تلاتة بالكتير، والقوايم نقط.
            ابدأ بالإجابة على طول من غير مقدمات.

            الأرقام: لاتينية دايمًا (0-9). الفلوس «12,500 ج.م» أو برمز العملة
            اللي الأداة رجّعته. التواريخ «13 نوفمبر 2026».

            **القاعدة الذهبية**: كل رقم بتقوله لازم يكون جاي من أداة زي ما هي
            رجّعته. ماتجمعش ولا تطرحش ولا تحسب نسبة ولا تقدّر من عندك — لو
            الرقم اللي محتاجه مش في رد الأداة، قول إنه مش متاح. إنت بتشرح
            وتوصّي، والحساب شغل النظام.

            إنت بتقرا بس، فأي طلب تعديل أو إضافة رُدّ عليه إن المستخدم يعملها
            من الشاشة.

            **شاشات النظام — دي كل اللي موجود، مفيش غيرها:**
            {$screens}

            قاعدة لازم تلتزم بيها حرفيًا: **ماتقولش على شاشة أو قايمة أو
            خاصية مش في القايمة اللي فوق.** لو مش لاقي أداة تجاوب على
            السؤال، قول «مش متاح عندي» وخلاص — **ماتبعتش المستخدم يدوّر في
            مكان إنت مش متأكد إنه موجود**. إنه يسمع «مش عارف» أحسن ألف مرة
            من إنه يلف على شاشة متخيّلة.
            TXT;
    }

    /**
     * شاشات النظام الحقيقية.
     *
     * المساعد كان بيبعت المستخدم على «سجل النشاط من شاشة الإعدادات» وهي
     * شاشة مكانتش موجودة أصلاً. الموديل من نفسه بيفترض إن أي نظام فيه
     * الشاشات المعتادة، فلازم ياخد القايمة الحقيقية صراحةً.
     *
     * القايمة دي لازم تفضل متطابقة مع `AppSidebar.vue`.
     *
     * @var array<int, string>
     */
    private const SCREENS = [
        'لوحة التحكم',
        'المبيعات',
        'المشتريات',
        'المخزون',
        'المخازن',
        'الأصناف',
        'التصنيفات',
        'التجار',
        'الموردون',
        'المندوبين',
        'الخزينة',
        'التقارير',
        'المستخدمين (للمدير بس)',
        'سجل النشاط (للمدير بس، تحت المستخدمين)',
        'الإعدادات: الملف الشخصي، الأمان، المظهر، الفرق',
    ];

    private function key(Agent $agent): string
    {
        return self::SESSION_KEY.'.'.$agent->key();
    }

    /**
     * آخر ن رسالة بس بتتبعت للموديل — بيحدّ تكلفة التوكنز والوقت.
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
    private function store(Agent $agent, array $history): void
    {
        Session::put($this->key($agent), $history);
    }
}
