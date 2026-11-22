<?php

namespace App\Ai;

use App\Ai\Agents\AdminAgent;
use App\Ai\Agents\Agent;
use App\Ai\Agents\InventoryAgent;
use App\Ai\Agents\PurchasingAgent;
use App\Ai\Agents\SalesAgent;
use App\Models\User;
use Laravel\Mcp\Server\Tool as McpTool;
use Prism\Prism\Tool;
use Prism\Prism\Tools\LaravelMcpTool;

/**
 * وكلاء المساعد — التبويبات اللي في لوحة «اسأل السيستم».
 *
 * الترتيب هنا هو ترتيب التبويبات على الشاشة.
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
     * كل الوكلاء.
     *
     * @return array<int, Agent>
     */
    public function all(): array
    {
        return array_map(fn (string $class): Agent => app($class), self::AGENTS);
    }

    /**
     * الوكلاء المسموحين لمستخدم.
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

    /**
     * وكيل بمفتاحه — أو الأول المسموح لو المفتاح مش معروف أو ممنوع.
     *
     * بنرجّع وكيل دايمًا بدل ما نرمي استثناء: المستخدم ممكن يكون معاه
     * لينك قديم أو دوره اتغيّر، والأنضف إنه يلاقي أول تبويب متاح قدامه
     * بدل شاشة خطأ.
     */
    public function resolve(User $user, ?string $key): ?Agent
    {
        $allowed = $this->for($user);

        if ($allowed === []) {
            return null;
        }

        foreach ($allowed as $agent) {
            if ($agent->key() === $key) {
                return $agent;
            }
        }

        return $allowed[0];
    }

    /**
     * أدوات وكيل كأدوات Prism.
     *
     * @return array<int, Tool>
     */
    public function toolsFor(Agent $agent): array
    {
        return array_values(array_filter(array_map(
            function (string $class): ?Tool {
                $tool = app($class);

                if (! $tool instanceof McpTool || ! $tool->eligibleForRegistration()) {
                    return null;
                }

                return new LaravelMcpTool($tool);
            },
            $agent->toolClasses(),
        )));
    }
}
