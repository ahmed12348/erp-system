<?php

namespace App\Ai;

use App\Ai\Agents\Agent;
use App\Exceptions\AiUnavailableException;
use App\Models\User;
use App\Services\AiService;

/**
 * بيقرّر السؤال ده بيخص أنهي مجال — قبل ما نجاوب عليه.
 *
 * المستخدم بيشوف شات واحد بيسأله أي حاجة. وراه بنعمل نداء تصنيف صغير
 * على الموديل الخفيف (تعليمات سطور، من غير أدوات، وكلمة أو اتنين رد)،
 * وبنبعت أدوات المجالات اللي طلعت منه بس.
 *
 * ليه نداء زيادة بدل ما نبعت كل الأدوات؟ لأن وصف كل أداة بيتبعت مع كل
 * جولة: تمنتاشر أداة في السلّة معناها تعليمات أطول، ووقت أطول على
 * استضافة بتقطع بعد 30 ثانية، واحتمال أكبر إن الموديل يختار الأداة
 * الغلط. نداء التصنيف على flash-lite أقل من ثانية — أرخص من الجولة
 * الزيادة اللي الاختيار الغلط بيسبّبها.
 *
 * ومهما حصل بيطلع بمجموعة أدوات: فشل النداء، أو رد مش مفهوم، أو وقت
 * ضيّق — كلها بتروح على آخر مجال اتستعمل، أو على المجموعة الافتراضية.
 * المستخدم عمره ما بيشوف «ما فهمتش السؤال».
 */
class AgentRouter
{
    public function __construct(
        private readonly AiService $ai,
        private readonly AgentRegistry $agents,
    ) {}

    /**
     * @param  array<int, string>  $previous  مجالات السؤال اللي فات
     */
    public function route(User $user, string $question, array $previous = [], ?float $deadline = null): Routing
    {
        $allowed = $this->agents->for($user);

        if ($allowed === []) {
            return new Routing([], Routing::SOURCE_NONE);
        }

        // مجال واحد متاح = مفيش قرار — التصنيف هيبقى نداء ضايع
        if (count($allowed) === 1) {
            return new Routing($allowed, Routing::SOURCE_ONLY);
        }

        if (! $this->affordable($deadline)) {
            return $this->fallback($user, $previous);
        }

        try {
            $answer = $this->ai->classify(
                $this->instruction($allowed, $previous),
                $question,
                $deadline,
            );
        } catch (AiUnavailableException) {
            // التوجيه مش يستاهل يوقع السؤال — بنكمّل من غيره
            return $this->fallback($user, $previous);
        }

        $keys = $this->parse($answer, $allowed);

        if ($keys === []) {
            return $this->fallback($user, $previous);
        }

        return new Routing($this->agents->byKeys($user, $keys), Routing::SOURCE_MODEL);
    }

    /**
     * تعليمات التصنيف.
     *
     * مقصودة قصيرة جدًا: دي أرخص نداء في النظام والمفروض تفضل كده.
     * المجالات الممنوعة على الدور مش بتتكتب أصلاً — الموديل ما يعرفش
     * بوجودها فما يقدرش يرشّحها.
     *
     * @param  array<int, Agent>  $allowed
     * @param  array<int, string>  $previous
     */
    private function instruction(array $allowed, array $previous): string
    {
        $domains = implode("\n", array_map(
            fn (Agent $agent): string => '- '.$agent->key().': '.$agent->description(),
            $allowed,
        ));

        /*
         * سؤال المتابعة بيفضل في نفس المجال.
         *
         * «وآخر دفعة؟» لوحده مالوش معنى؛ معناه جاي من السؤال اللي
         * قبله. من غير السطر ده الموديل بيوجّهه على مجال جديد كل مرة،
         * والمستخدم بياخد رد مالوش علاقة باللي كان بيتكلم فيه.
         */
        $context = $previous === []
            ? ''
            : "\n\nالسؤال اللي فات كان على: ".implode('، ', $previous)
                .".\nلو ده سؤال متابعة لنفس الموضوع، رُد بنفس المجال.";

        return <<<TXT
            صنّف سؤال المستخدم على مجالات نظام ERP.

            المجالات:
            {$domains}

            رُد بأسماء المجالات بالإنجليزي مفصولة بفاصلة، وبس — من غير أي
            كلام تاني ولا شرح.

            السؤال ممكن يحتاج أكتر من مجال (زي «مين عليه فلوس وإيه اللي
            قرب يخلص؟») — ساعتها رُد بكلهم.

            لو مش واضح، رُد بكلمة: unclear{$context}
            TXT;
    }

    /**
     * بيطلّع المفاتيح المعروفة من رد الموديل.
     *
     * بندوّر على المفتاح جوه النص بدل ما نقسّم على الفواصل: الموديل
     * ساعات بيرد «inventory, sales» وساعات «**inventory**» وساعات
     * بسطر شرح. اللي يهمنا المفاتيح اللي فعلاً اتذكرت.
     *
     * @param  array<int, Agent>  $allowed
     * @return array<int, string>
     */
    private function parse(string $answer, array $allowed): array
    {
        $text = mb_strtolower($answer);

        if (str_contains($text, 'unclear')) {
            return [];
        }

        $keys = [];

        foreach ($allowed as $agent) {
            if (str_contains($text, $agent->key())) {
                $keys[] = $agent->key();
            }
        }

        return $keys;
    }

    /**
     * التوجيه ما وصلش لحاجة.
     *
     * آخر مجال اتستعمل أحسن من المجموعة الافتراضية: السؤال اللي الموجّه
     * ما فهمهوش غالبًا متابعة («وده؟»، «طب والتاني؟»).
     *
     * @param  array<int, string>  $previous
     */
    private function fallback(User $user, array $previous): Routing
    {
        $agents = $this->agents->byKeys($user, $previous);

        if ($agents !== []) {
            return new Routing($agents, Routing::SOURCE_PREVIOUS);
        }

        return new Routing([$this->agents->general()], Routing::SOURCE_DEFAULT);
    }

    /**
     * فاضل وقت يستاهل ننفق منه على توجيه؟
     *
     * الإجابة أهم من دقة التوجيه. لو الميزانية ضيّقة بنروح على
     * المجموعة الافتراضية على طول بدل ما ناكل التانية اللي فاضلة.
     */
    private function affordable(?float $deadline): bool
    {
        if ($deadline === null) {
            return true;
        }

        return ($deadline - microtime(true)) >= (float) config('ai.routing.reserve');
    }
}
