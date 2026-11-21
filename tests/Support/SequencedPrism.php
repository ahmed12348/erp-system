<?php

namespace Tests\Support;

use Prism\Prism\Enums\Provider as ProviderEnum;
use Prism\Prism\PrismManager;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Testing\PrismFake;
use Prism\Prism\Text\Request as TextRequest;
use Prism\Prism\Text\Response as TextResponse;
use Throwable;

/**
 * مزوّد متزيّف بيمشي على سيناريو فيه أعطال وردود مخلوطة.
 *
 * `Prism::fake()` بياخد ردود بس، و`FailingPrism` بيقع دايمًا — ومحدش
 * فيهم ينفع لاختبار «اتزحم، جرّب تاني، وبعدين حوّل للموديل البديل»،
 * لأنها محتاجة عطل ورا عطل ورا رد ناجح.
 *
 * بيسجّل كمان اسم الموديل في كل محاولة، فنقدر نتأكد إن البديل
 * اتستعمل في الوقت الصح.
 */
class SequencedPrism extends PrismFake
{
    /** @var array<int, TextResponse|StructuredResponse|Throwable> */
    private array $script;

    private int $position = 0;

    /** @var array<int, string> */
    private array $models = [];

    /**
     * @param  array<int, TextResponse|StructuredResponse|Throwable>  $script
     */
    public function __construct(array $script)
    {
        parent::__construct([]);

        $this->script = $script;
    }

    /**
     * @param  array<int, TextResponse|StructuredResponse|Throwable>  $script
     */
    public static function fake(array $script): self
    {
        $fake = new self($script);

        app()->instance(PrismManager::class, new class($fake) extends PrismManager
        {
            public function __construct(private readonly SequencedPrism $fake) {}

            public function resolve(ProviderEnum|string $name, array $providerConfig = []): SequencedPrism
            {
                return $this->fake;
            }
        });

        return $fake;
    }

    /**
     * أسماء الموديلات اللي اتجرّبت بالترتيب.
     *
     * @return array<int, string>
     */
    public function models(): array
    {
        return $this->models;
    }

    #[\Override]
    public function text(TextRequest $request): TextResponse
    {
        $this->models[] = $request->model();

        /** @var TextResponse $response */
        $response = $this->next();

        return $response;
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        $this->models[] = $request->model();

        /** @var StructuredResponse $response */
        $response = $this->next();

        return $response;
    }

    private function next(): TextResponse|StructuredResponse
    {
        if (! isset($this->script[$this->position])) {
            throw new \RuntimeException('SequencedPrism ran out of scripted outcomes.');
        }

        $outcome = $this->script[$this->position];

        $this->position++;

        if ($outcome instanceof Throwable) {
            throw $outcome;
        }

        return $outcome;
    }
}
