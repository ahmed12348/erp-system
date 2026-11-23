<?php

namespace App\Ai;

use App\Ai\Agents\AdminAgent;
use App\Ai\Agents\Agent;
use App\Ai\Agents\GeneralAgent;
use App\Ai\Agents\InventoryAgent;
use App\Ai\Agents\PurchasingAgent;
use App\Ai\Agents\SalesAgent;
use App\Models\User;
use Laravel\Mcp\Server\Tool as McpTool;
use Prism\Prism\Tool;
use Prism\Prism\Tools\LaravelMcpTool;

/**
 * مجالات المساعد.
 *
 * المستخدم بيشوف شات واحد؛ المجالات دي بتتستعمل وراه عشان نبعت أدوات
 * السؤال بس. `AgentRouter` بيختار منها، والـ registry دي بتجمّع
 * أدواتهم وتعليماتهم وأسئلتهم.
 *
 * الترتيب هنا بيتحكم في ترتيب الأسئلة المقترحة.
 */
class AgentRegistry
{
    /** @var array<int, class-string<Agent>> */
    private const AGENTS = [
        InventoryAgent::class,
        SalesAgent::class,
        PurchasingAgent::class,
        AdminAgent::class,
    ];

    /**
     * كل المجالات اللي ينفع يتوجّه عليها.
     *
     * `GeneralAgent` مش منهم عن قصد — ده شبكة الأمان مش مجال.
     *
     * @return array<int, Agent>
     */
    public function all(): array
    {
        return array_map(fn (string $class): Agent => app($class), self::AGENTS);
    }

    /**
     * المجالات المسموحة لمستخدم.
     *
     * @return array<int, Agent>
     */
    public function for(User $user): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (Agent $agent): bool => $agent->allows($user),
        ));
    }

    /** المجموعة الافتراضية لما التوجيه مايوصلش لحاجة */
    public function general(): Agent
    {
        return app(GeneralAgent::class);
    }

    /**
     * مجالات بمفاتيحها — المسموح منها بس.
     *
     * المفتاح المش معروف أو الممنوع بيتشال في صمت: ده بيخلّي رد الموجّه
     * الغلط («admin» لمندوب) مايفتحش حاجة، من غير ما يوقع السؤال.
     *
     * @param  array<int, string>  $keys
     * @return array<int, Agent>
     */
    public function byKeys(User $user, array $keys): array
    {
        return array_values(array_filter(
            $this->for($user),
            fn (Agent $agent): bool => in_array($agent->key(), $keys, true),
        ));
    }

    /**
     * أدوات مجال أو مجموعة مجالات كأدوات Prism.
     *
     * `$user` هو الحارس: الأدوات بتتصفّى على اللي دوره بيوصله فعلاً،
     * فحتى لو حد بعت مجال ممنوع أو المجموعة الافتراضية فيها أداة
     * إدارية، المندوب مش هياخدها.
     *
     * @param  Agent|array<int, Agent>  $agents
     * @return array<int, Tool>
     */
    public function toolsFor(Agent|array $agents, ?User $user = null): array
    {
        $classes = $this->classesOf(is_array($agents) ? $agents : [$agents]);

        if ($user !== null) {
            $classes = array_values(array_intersect(
                $classes,
                $this->classesOf($this->for($user)),
            ));
        }

        return array_values(array_filter(array_map(
            function (string $class): ?Tool {
                $tool = app($class);

                if (! $tool instanceof McpTool || ! $tool->eligibleForRegistration()) {
                    return null;
                }

                return new LaravelMcpTool($tool);
            },
            $classes,
        )));
    }

    /**
     * تعليمات المجالات اللي السؤال اتوجّه عليها.
     *
     * مجال واحد بياخد تعليماته كاملة. أكتر من مجال بياخدوا سطر لكل
     * واحد — التعليمات بتتبعت مع كل جولة أدوات، فأربع مجموعات كاملة
     * بتتحوّل لوقت على استضافة بتقطع بعد 30 ثانية.
     *
     * @param  array<int, Agent>  $agents
     */
    public function specialismFor(array $agents): string
    {
        if ($agents === []) {
            return '';
        }

        if (count($agents) === 1) {
            return $agents[0]->instructions();
        }

        $lines = array_map(
            fn (Agent $agent): string => '- '.$agent->brief(),
            $agents,
        );

        return "السؤال ده بيلمس أكتر من مجال:\n".implode("\n", $lines);
    }

    /**
     * الأسئلة المقترحة — مخلوطة من كل المجالات اللي الدور بيوصلها.
     *
     * بالتناوب مش بالترتيب: سؤال من كل مجال قبل ما نرجع لمجال خد
     * دوره. اللوحة فيها شات واحد، فالمفروض أول نظرة تقول للمستخدم
     * إنها بتجاوب على أي حاجة، مش على المخزن بس.
     *
     * @return array<int, string>
     */
    public function startersFor(User $user, ?int $limit = null): array
    {
        $limit ??= (int) config('ai.starters_limit', 6);
        $pools = array_map(fn (Agent $agent): array => $agent->starters(), $this->for($user));

        $mixed = [];

        for ($round = 0; count($mixed) < $limit; $round++) {
            $added = false;

            foreach ($pools as $pool) {
                if (! isset($pool[$round])) {
                    continue;
                }

                $added = true;

                if (! in_array($pool[$round], $mixed, true)) {
                    $mixed[] = $pool[$round];
                }

                if (count($mixed) >= $limit) {
                    break;
                }
            }

            // خلصت أسئلة كل المجالات قبل ما نوصل للعدد المطلوب
            if (! $added) {
                break;
            }
        }

        return $mixed;
    }

    /**
     * كلاسات أدوات مجموعة مجالات، من غير تكرار.
     *
     * @param  array<int, Agent>  $agents
     * @return array<int, class-string>
     */
    private function classesOf(array $agents): array
    {
        $classes = [];

        foreach ($agents as $agent) {
            foreach ($agent->toolClasses() as $class) {
                if (! in_array($class, $classes, true)) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }
}
