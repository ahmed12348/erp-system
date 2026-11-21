<?php

namespace App\Services;

use App\Exceptions\AiUnavailableException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Contracts\Message;
use Prism\Prism\Contracts\Schema;
use Prism\Prism\Exceptions\PrismStructuredDecodingException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Tool;
use Prism\Prism\ValueObjects\Media\Document;
use Prism\Prism\ValueObjects\Media\Image;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
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
 */
class AiService
{
    public function isEnabled(): bool
    {
        return (bool) config('ai.enabled') && $this->hasApiKey();
    }

    public function provider(): string
    {
        return (string) config('ai.provider');
    }

    /**
     * سؤال وجواب مع أدوات القراءة.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  array<int, Tool>  $tools
     *
     * @throws AiUnavailableException
     */
    public function chat(string $systemPrompt, array $history, array $tools): string
    {
        $this->guard();

        try {
            $response = Prism::text()
                ->using($this->provider(), (string) config('ai.models.chat'))
                ->withSystemPrompt($systemPrompt)
                ->withMessages($this->toMessages($history))
                ->withTools($tools)
                ->withMaxSteps((int) config('ai.max_steps'))
                ->withMaxTokens((int) config('ai.max_tokens'))
                ->asText();
        } catch (Throwable $e) {
            throw $this->translate($e, 'chat');
        }

        $text = trim($response->text);

        if ($text === '') {
            throw AiUnavailableException::from(
                new \RuntimeException('Empty response text from provider.')
            );
        }

        return $text;
    }

    /**
     * قراءة صورة أو PDF ورجوع JSON متقيّد بالـ schema.
     *
     * @return array<string, mixed>
     *
     * @throws AiUnavailableException
     */
    public function extract(string $prompt, UploadedFile $file, Schema $schema): array
    {
        $this->guard();

        try {
            $response = Prism::structured()
                ->using($this->provider(), (string) config('ai.models.vision'))
                ->withSchema($schema)
                ->withMessages([new UserMessage($prompt, [$this->mediaFor($file)])])
                ->withMaxTokens((int) config('ai.max_tokens'))
                ->asStructured();
        } catch (Throwable $e) {
            throw $this->translate($e, 'extract');
        }

        $structured = $response->structured;

        if (! is_array($structured) || $structured === []) {
            throw AiUnavailableException::from(
                new PrismStructuredDecodingException('Empty structured response.')
            );
        }

        return $structured;
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
     */
    private function translate(Throwable $e, string $operation): AiUnavailableException
    {
        $translated = AiUnavailableException::from($e);

        Log::warning('AI request failed', [
            'operation' => $operation,
            'provider' => $this->provider(),
            'reason' => $translated->reason,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        return $translated;
    }
}
