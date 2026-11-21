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
 * مزوّد متزيّف بيقع دايمًا.
 *
 * `Prism::fake()` بيرجّع ردود محضّرة بس — مافيهوش طريقة تخليه يرمي
 * استثناء، وإحنا محتاجين نتأكد إن كل نوع عطل بيوصل للمستخدم برسالته
 * العربية الصح. فبنورّث منه ونرمي العطل اللي احنا عايزينه.
 */
class FailingPrism extends PrismFake
{
    public function __construct(private readonly Throwable $error)
    {
        parent::__construct([]);
    }

    /**
     * بيركّب المزوّد الفاشل مكان المزوّد الحقيقي — بنفس طريقة
     * `Prism::fake()` بالظبط.
     */
    public static function fake(Throwable $error): self
    {
        $fake = new self($error);

        app()->instance(PrismManager::class, new class($fake) extends PrismManager
        {
            public function __construct(private readonly FailingPrism $fake) {}

            public function resolve(ProviderEnum|string $name, array $providerConfig = []): FailingPrism
            {
                return $this->fake;
            }
        });

        return $fake;
    }

    #[\Override]
    public function text(TextRequest $request): TextResponse
    {
        throw $this->error;
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        throw $this->error;
    }
}
