<?php

namespace App\Services;

use App\Exceptions\AiUnavailableException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Contracts\Message;
use Prism\Prism\Contracts\Schema;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Exceptions\PrismStructuredDecodingException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\Tool;
use Prism\Prism\ValueObjects\Media\Document;
use Prism\Prism\ValueObjects\Media\Image;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\ToolResultMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

/**
 * الواجهة الوحيدة للموديل في النظام كله.
 *
 * المحادثة ومسح الفواتير الاتنين بيعدّوا من هنا، فتبديل المزوّد من
 * `.env` بيغيّر الاتنين مع بعض، وأي عطل بيترجم لرسالة عربية واحدة في
 * مكان واحد.
 *
 * كله HTTP عادي من غير streaming — الاستضافة المشتركة مقفول فيها
 * `proc_open` و`exec`، و SSE مش بيعدّي من البروكسي بتاعها أصلًا.
 *
 * الوقت هو القيد الحاكم هنا: PHP على الاستضافة بيقطع التنفيذ بعد
 * حوالي 30 ثانية، فكل نداء ليه مهلة، والطلب كله ليه ميزانية أقصر من
 * حد PHP. لو الميزانية خلصت بنرمي عطل عربي بنفسنا بدل ما السيرفر
 * يرمي صفحة 500 بيضا.
 */
class AiService
{
    /**
     * أقل وقت فاضل يستاهل إننا نجرّب نداء جديد.
     *
     * أقل من كده النداء هيتقطع في نصّه على أي حال، فالأنضف إننا نقول
     * للمستخدم «خد وقت» بدل ما نستنى ونتقطع.
     */
    private const MIN_SLICE_SECONDS = 4.0;

    /**
     * رد التوجيه المتوقّع كلمة أو اتنين («inventory, sales»)، فالسقف
     * ده كفاية وزيادة — وبيمنع إن الموديل يسترسل لو اتلخبط.
     */
    private const ROUTING_MAX_TOKENS = 32;

    public function isEnabled(): bool
    {
        return (bool) config('ai.enabled') && $this->hasApiKey();
    }

    public function provider(): string
    {
        return (string) config('ai.provider');
    }

    /**
     * بداية ميزانية طلب جديد.
     *
     * بيترجع وقت نهاية بالثواني (ساعة الحيط). اللي بينادي أكتر من نداء
     * في طلب واحد بيحسبها مرة ويمرّرها للكل، فالمجموع بيفضل تحت
     * الميزانية مهما كان عدد النداءات.
     */
    public function deadline(): float
    {
        return microtime(true) + (float) config('ai.budget');
    }

    /**
     * نداء تصنيف صغير: سؤال واحد، من غير أدوات ولا تاريخ.
     *
     * بيتستعمل في توجيه السؤال لمجاله قبل الإجابة. مقصود إنه رخيص —
     * موديل خفيف، تعليمات سطور، ومخرجات كلمة أو كلمتين — عشان ما
     * ياكلش من ميزانية الإجابة.
     *
     * @throws AiUnavailableException
     */
    public function classify(string $instruction, string $question, ?float $deadline = null): string
    {
        $this->guard();

        $response = $this->attempt(
            'route',
            $deadline ?? $this->deadline(),
            fn (string $model, int $timeout) => Prism::text()
                ->using($this->provider(), $model)
                ->withSystemPrompt($instruction)
                ->withPrompt($question)
                ->withMaxTokens(self::ROUTING_MAX_TOKENS)
                ->withProviderOptions($this->thinkingFor('route'))
                ->withClientOptions($this->clientOptions($timeout))
                ->asText(),
        );

        return trim($response->text);
    }

    /**
     * سؤال وجواب مع أدوات القراءة.
     *
     * جولات الأدوات بتتدار من هنا مش من جوه Prism: كل جولة بنحسب
     * الوقت الفاضل من الميزانية ونديه للنداء الجاي. كده الطلب كله
     * بيفضل تحت الميزانية مهما الموديل طلب أدوات كتير — وده اللي
     * `withMaxSteps` لوحده ما بيضمنهوش، لأنه بيعدّ جولات مش ثواني.
     *
     * `$deadline` بيتبعت من بره لما الطلب فيه أكتر من نداء (نداء
     * التوجيه وبعده الإجابة) — عشان الاتنين يتقاسموا **ميزانية واحدة**
     * بدل ما كل واحد ياخد ميزانية كاملة ويعدّوا حد PHP مع بعض.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  array<int, Tool>  $tools
     *
     * @throws AiUnavailableException
     */
    public function chat(string $systemPrompt, array $history, array $tools, ?float $deadline = null): string
    {
        $this->guard();

        $deadline ??= $this->deadline();
        $messages = $this->toMessages($history);
        $maxSteps = max(1, (int) config('ai.max_steps'));

        for ($step = 0; $step < $maxSteps; $step++) {
            $response = $this->text($systemPrompt, $messages, $tools, $deadline);

            if ($response->finishReason !== FinishReason::ToolCalls) {
                return $this->textOf($response);
            }

            /*
             * Prism نفّذ الأدوات قبل ما يوقف عند الجولة الواحدة، فإحنا
             * بناخد الرسايل اللي رجّعها ونضيف نتايج الأدوات وراها،
             * وندّي الموديل فرصة يرد.
             */
            $messages = [
                ...$response->messages->all(),
                new ToolResultMessage($response->toolResults),
            ];
        }

        // استهلك كل الجولات وهو لسه بينادي أدوات — مش هيوصل لرد مفيد
        throw $this->logged(
            AiUnavailableException::timedOut(),
            'chat',
            ['steps' => $maxSteps],
        );
    }

    /**
     * قراءة صورة أو PDF ورجوع JSON متقيّد بالـ schema.
     *
     * نداء واحد من غير أدوات، وبتفكير طبيعي — الصورة المزحومة محتاجة
     * الموديل ياخد وقته، عكس أسئلة المحادثة.
     *
     * @return array<string, mixed>
     *
     * @throws AiUnavailableException
     */
    public function extract(string $prompt, UploadedFile $file, Schema $schema): array
    {
        $this->guard();

        $deadline = microtime(true) + (float) config('ai.budget');
        $message = new UserMessage($prompt, [$this->mediaFor($file)]);

        $response = $this->attempt('extract', $deadline, fn (string $model, int $timeout) => Prism::structured()
            ->using($this->provider(), $model)
            ->withSchema($schema)
            ->withMessages([$message])
            ->withMaxTokens((int) config('ai.max_tokens'))
            ->withProviderOptions($this->thinkingFor('vision'))
            ->withClientOptions($this->clientOptions($timeout))
            ->asStructured());

        $structured = $response->structured;

        if (! is_array($structured) || $structured === []) {
            throw $this->logged(
                AiUnavailableException::from(
                    new PrismStructuredDecodingException('Empty structured response.')
                ),
                'extract',
            );
        }

        return $structured;
    }

    /**
     * جولة واحدة من المحادثة.
     *
     * `withMaxSteps(1)` عن قصد — دورة الأدوات بتتدار في `chat()` فوق
     * عشان نقدر نحسب الوقت الفاضل قبل كل جولة.
     *
     * @param  array<int, Message>  $messages
     * @param  array<int, Tool>  $tools
     *
     * @throws AiUnavailableException
     */
    private function text(string $systemPrompt, array $messages, array $tools, float $deadline): TextResponse
    {
        return $this->attempt('chat', $deadline, fn (string $model, int $timeout) => Prism::text()
            ->using($this->provider(), $model)
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
            ->withTools($tools)
            ->withMaxSteps(1)
            ->withMaxTokens((int) config('ai.max_tokens'))
            ->withProviderOptions($this->thinkingFor('chat'))
            ->withClientOptions($this->clientOptions($timeout))
            ->asText());
    }

    /**
     * بينفّذ النداء، وبيعيد المحاولة مرة على 503، وبعدين بيجرّب الموديل
     * البديل.
     *
     * كل محاولة بتاخد مهلتها من الوقت الفاضل في الميزانية لحظتها، فلو
     * المحاولة الأولى أكلت الوقت، اللي بعدها بتترفض من الأساس بدل ما
     * تمشي وتتقطع في نصّها.
     *
     * @template T
     *
     * @param  callable(string, int): T  $call
     * @param  array<string, mixed>  $context
     * @return T
     *
     * @throws AiUnavailableException
     */
    private function attempt(string $operation, float $deadline, callable $call, array $context = []): mixed
    {
        $model = (string) config("ai.models.{$this->modelKey($operation)}");
        $fallback = (string) config('ai.models.fallback');

        // البديل مالوش لازمة لو هو نفسه الأساسي
        $hasFallback = $fallback !== '' && $fallback !== $model;

        /*
         * الأساسي، وبعدين هو تاني (إعادة المحاولة على 503)، وبعدين
         * البديل.
         *
         * التوجيه استثناء: محاولة واحدة وبس. فشله رخيص — بنكمّل
         * بالمجموعة الافتراضية — وإعادة المحاولة عليه بتاكل من
         * ميزانية الإجابة، وهي اللي المستخدم مستني عليها فعلاً.
         */
        $plan = match (true) {
            $operation === 'route' => [$model],
            $hasFallback => [$model, $model, $fallback],
            default => [$model, $model],
        };

        $last = null;
        $index = 0;

        while ($index < count($plan)) {
            $candidate = $plan[$index];

            try {
                return $call($candidate, $this->sliceFor($deadline, $operation, $context));
            } catch (AiUnavailableException $e) {
                // الميزانية خلصت — مفيش وقت لمحاولة تانية أصلاً
                throw $e;
            } catch (Throwable $e) {
                $last = $e;
                $reason = AiUnavailableException::from($e)->reason;

                // الزحمة والموديل الناقص والتأخير بس هما اللي يستاهلوا محاولة تانية
                if (! in_array($reason, ['overloaded', 'model_not_found', 'timeout'], true)) {
                    break;
                }

                Log::warning('AI attempt failed, retrying', [
                    'operation' => $operation,
                    'model' => $candidate,
                    'reason' => $reason,
                ]);

                /*
                 * الموديل اللي اتأخر مرة هيتأخر تاني، فإعادة المحاولة عليه
                 * بتاكل اللي فاضل من الميزانية من غير فايدة. بنقفز للبديل
                 * الأخف على طول — ولو الوقت خلص، `sliceFor` هي اللي هتوقف
                 * المحاولة دي من أصلها.
                 */
                $next = $reason === 'timeout' && $hasFallback
                    ? count($plan) - 1
                    : $index + 1;

                // لازم نتقدّم خطوة على الأقل عشان ما ندورش في مكاننا
                $index = max($next, $index + 1);
            }
        }

        throw $this->logged(
            AiUnavailableException::from($last ?? new \RuntimeException('AI call failed.')),
            $operation,
            [...$context, 'model' => $model, 'fallback' => $fallback],
        );
    }

    /**
     * المهلة المسموحة للنداء الجاي — الأقل بين مهلة النداء الواحدة
     * واللي فاضل من الميزانية.
     *
     * @param  array<string, mixed>  $context
     *
     * @throws AiUnavailableException
     */
    private function sliceFor(float $deadline, string $operation, array $context = []): int
    {
        $remaining = $deadline - microtime(true);

        if ($remaining < self::MIN_SLICE_SECONDS) {
            throw $this->logged(
                AiUnavailableException::timedOut(),
                $operation,
                [...$context, 'remaining' => round($remaining, 2)],
            );
        }

        return (int) max(1, min($this->timeoutFor($operation), (int) floor($remaining)));
    }

    /**
     * سقف النداء الواحد.
     *
     * التوجيه ليه سقف أقصر: تصنيف جملة واحدة لو أخد أكتر من كده يبقى
     * في حاجة غلط، والاستنى عليه بياكل من وقت الإجابة.
     */
    private function timeoutFor(string $operation): int
    {
        return $operation === 'route'
            ? (int) config('ai.routing.timeout')
            : (int) config('ai.timeout');
    }

    /**
     * @return array<string, mixed>
     */
    private function clientOptions(int $timeout): array
    {
        return [
            'timeout' => $timeout,
            // الاتصال نفسه لازم يتعمل بسرعة — البطء الحقيقي في التوليد
            'connect_timeout' => min(5, $timeout),
        ];
    }

    /**
     * إعدادات التفكير للمزوّد الحالي.
     *
     * @return array<string, mixed>
     */
    private function thinkingFor(string $operation): array
    {
        /** @var array<string, mixed> $options */
        $options = config("ai.thinking.{$this->provider()}.{$operation}", []);

        return array_filter($options, fn ($value): bool => filled($value));
    }

    private function modelKey(string $operation): string
    {
        return match ($operation) {
            'extract' => 'vision',
            'route' => 'routing',
            default => 'chat',
        };
    }

    /**
     * @throws AiUnavailableException
     */
    private function textOf(TextResponse $response): string
    {
        $text = trim($response->text);

        if ($text === '') {
            throw $this->logged(
                AiUnavailableException::from(
                    new \RuntimeException('Empty response text from provider.')
                ),
                'chat',
            );
        }

        return $text;
    }

    /**
     * الملف المرفوع كجزء من الرسالة.
     *
     * بنبعت المحتوى الخام ومعاه الـ mime بدل المسار، لأن الملف المرفوع
     * بيتخزّن في مسار مؤقت من غير امتداد فاكتشاف النوع منه مش مضمون.
     */
    private function mediaFor(UploadedFile $file): Document|Image
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $contents = (string) file_get_contents($file->getRealPath());

        return $mime === 'application/pdf'
            ? Document::fromRawContent($contents, $mime)
            : Image::fromRawContent($contents, $mime);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, Message>
     */
    private function toMessages(array $history): array
    {
        return array_map(
            fn (array $message) => $message['role'] === 'assistant'
                ? new AssistantMessage($message['content'])
                : new UserMessage($message['content']),
            $history,
        );
    }

    /**
     * @throws AiUnavailableException
     */
    private function guard(): void
    {
        if (! config('ai.enabled')) {
            throw AiUnavailableException::disabled();
        }

        if (! $this->hasApiKey()) {
            throw AiUnavailableException::notConfigured();
        }
    }

    private function hasApiKey(): bool
    {
        return filled(config('prism.providers.'.$this->provider().'.api_key'));
    }

    /**
     * التفاصيل الفنية بتروح للّوج، والمستخدم بياخد العربي بس.
     *
     * المستوى `error` عن قصد: على السيرفر `LOG_LEVEL=error`، وعطل
     * المساعد لازم يبان هناك من غير ما نفتح اللوج كله.
     *
     * التوجيه استثناء — بيتسجّل `warning`: فشله مابيوقعش الطلب، بنكمّل
     * بالمجموعة الافتراضية والمستخدم بياخد رده عادي. لو كان `error`
     * كنا هنملا لوج الإنتاج بأعطال المستخدم أصلاً ما حسّش بيها.
     *
     * @param  array<string, mixed>  $context
     */
    private function logged(AiUnavailableException $e, string $operation, array $context = []): AiUnavailableException
    {
        $previous = $e->getPrevious();
        $level = $operation === 'route' ? 'warning' : 'error';

        Log::{$level}('AI request failed', [
            'operation' => $operation,
            'provider' => $this->provider(),
            'reason' => $e->reason,
            'exception' => $previous !== null ? $previous::class : $e::class,
            'message' => $previous?->getMessage() ?? $e->getMessage(),
            ...$context,
        ]);

        return $e;
    }
}
