<?php

namespace App\Ai;

use App\Ai\Agents\Agent;

/**
 * نتيجة توجيه سؤال: المجالات اللي هيتبعت أدواتها، ومنين جت.
 *
 * `source` مش زينة — بتتسجّل وبتتقري في الاختبارات، وبتفرّق في قرار
 * واحد مهم: المجالات اللي الموجّه اختارها بتتفكر للسؤال اللي بعده،
 * والمجموعة الافتراضية لأ (مش مجال عشان نكمّل عليه).
 */
final class Routing
{
    public const SOURCE_MODEL = 'model';

    public const SOURCE_PREVIOUS = 'previous';

    public const SOURCE_DEFAULT = 'default';

    public const SOURCE_ONLY = 'only';

    public const SOURCE_NONE = 'none';

    /**
     * @param  array<int, Agent>  $agents
     */
    public function __construct(
        public readonly array $agents,
        public readonly string $source,
    ) {}

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_map(fn (Agent $agent): string => $agent->key(), $this->agents);
    }

    public function isEmpty(): bool
    {
        return $this->agents === [];
    }

    /** المجالات دي تستاهل تتفكر للسؤال الجاي؟ */
    public function remembered(): bool
    {
        return in_array($this->source, [self::SOURCE_MODEL, self::SOURCE_PREVIOUS, self::SOURCE_ONLY], true);
    }
}
