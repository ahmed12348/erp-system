<?php

namespace App\Services;

use App\Ai\AgentRegistry;
use App\Ai\AgentRouter;
use App\Ai\Routing;
use App\Exceptions\AiUnavailableException;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * «اسأل السيستم» — محادثة قراءة فقط على بيانات النظام.
 *
 * **شات واحد بيتسأل فيه أي حاجة.** كان فيه تبويبات (مخازن/مبيعات/
 * مشتريات/إدارة) والمستخدم هو اللي بيختار؛ طلع إن ده بيحمّله قرار هو
 * مش عايزه — هو عنده سؤال مش عنده تصنيف. فالتقسيم فضل موجود في الكود
 * بس بقى وراه: `AgentRouter` بيصنّف السؤال قبل الإجابة، وبنبعت أدوات
 * مجاله بس.
 *
 * الميزانية واحدة للطلب كله: نداء التوجيه ونداء الإجابة بيتقاسموها،
 * فالمجموع بيفضل تحت حد تنفيذ PHP على الاستضافة المشتركة.
 *
 * التاريخ محفوظ في الجلسة بس: المستخدم يقفل الصفحة ويرجع يلاقيه،
 * ويعمل تسجيل خروج يتمسح.
 */
class AskSystemService
{
    private const MESSAGES_KEY = 'ai.chat.messages';

    /** آخر مجالات اتوجّه عليها — عشان سؤال المتابعة يفضل في موضوعه */
    private const DOMAINS_KEY = 'ai.chat.domains';

    public function __construct(
        private readonly AiService $ai,
        private readonly AgentRegistry $agents,
        private readonly AgentRouter $router,
    ) {}

    /**
     * بيوجّه السؤال، يسأل الموديل، ويحفظ الرد.
     *
     * لو المساعد وقع، سؤال المستخدم بيترجع من الجلسة عشان المحادثة
     * ماتفضلش فيها سؤال من غير رد.
     *
     * @return array{reply: array{role: string, content: string}, domains: array<int, string>}
     *
     * @throws AiUnavailableException
     */
    public function ask(User $user, string $question): array
    {
        $history = $this->history();
        $history[] = ['role' => 'user', 'content' => $question];

        // ميزانية واحدة للتوجيه والإجابة مع بعض
        $deadline = $this->ai->deadline();

        $routing = $this->router->route($user, $question, $this->domains(), $deadline);

        try {
            $answer = $this->ai->chat(
                $this->systemPrompt($routing),
                $this->trimmed($history),
                $this->agents->toolsFor($routing->agents, $user),
                $deadline,
            );
        } catch (AiUnavailableException $e) {
            $this->store($this->trimmed($history));

            throw $e;
        }

        $history[] = ['role' => 'assistant', 'content' => $answer];

        $this->store($this->trimmed($history));
        $this->remember($routing);

        return [
            'reply' => ['role' => 'assistant', 'content' => $answer],
            'domains' => $routing->keys(),
        ];
    }

    /**
     * رسايل المحادثة.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function history(): array
    {
        /** @var array<int, array{role: string, content: string}> $messages */
        $messages = Session::get(self::MESSAGES_KEY, []);

        return $messages;
    }

    public function clear(): void
    {
        Session::forget(self::MESSAGES_KEY);
        Session::forget(self::DOMAINS_KEY);
    }

    /**
     * مجالات آخر سؤال.
     *
     * @return array<int, string>
     */
    public function domains(): array
    {
        /** @var array<int, string> $keys */
        $keys = Session::get(self::DOMAINS_KEY, []);

        return $keys;
    }

    /**
     * تعليمات الموديل — القواعد العامة + تخصص المجالات اللي اتوجّه عليها.
     *
     * مقصودة قصيرة: كل سطر بيتبعت مع كل جولة أدوات، يعني بيتحاسب عليه
     * مرتين وتلاتة في السؤال الواحد. وصف كل أداة بيتبعت مع الأداة
     * نفسها، فمالوش لازمة يتكرّر هنا.
     */
    public function systemPrompt(Routing $routing): string
    {
        $screens = implode('، ', self::SCREENS);
        $specialism = $this->agents->specialismFor($routing->agents);

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
    private function store(array $history): void
    {
        Session::put(self::MESSAGES_KEY, $history);
    }

    /**
     * بيفتكر المجالات للسؤال الجاي.
     *
     * المجموعة الافتراضية مش بتتفكر: هي مش مجال، وفضلها محفوظة معناها
     * إن أول سؤال مش واضح بيقفل التوجيه على باقي المحادثة.
     */
    private function remember(Routing $routing): void
    {
        if ($routing->remembered()) {
            Session::put(self::DOMAINS_KEY, $routing->keys());

            return;
        }

        Session::forget(self::DOMAINS_KEY);
    }
}
