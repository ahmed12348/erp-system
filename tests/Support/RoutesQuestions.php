<?php

namespace Tests\Support;

use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Response as TextResponse;

/**
 * كل سؤال بيتسأل بقى نداءين: التوجيه الأول، وبعده الإجابة.
 *
 * الـ helper ده بيخلّي ده واضح في الاختبارات بدل ما كل واحد يحط
 * `TextResponseFake::make()->withText('inventory')` وياخد باله لوحده إن
 * أول طلب مسجّل هو التوجيه مش السؤال.
 */
trait RoutesQuestions
{
    /** رد نداء التوجيه — المجالات اللي الموديل اختارها */
    protected function routedTo(string ...$domains): TextResponse
    {
        return TextResponseFake::make()->withText(implode(', ', $domains));
    }

    /** رد توجيه مش مفهوم — بيوقّع السؤال على المجموعة الافتراضية */
    protected function unclearRouting(): TextResponse
    {
        return TextResponseFake::make()->withText('unclear');
    }
}
