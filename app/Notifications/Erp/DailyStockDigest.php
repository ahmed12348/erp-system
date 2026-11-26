<?php

namespace App\Notifications\Erp;

use App\Models\Product;
use App\Models\User;
use App\Notifications\Erp\Concerns\RestrictsChannels;
use App\Services\NotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * ملخّص النواقص اليومي — كل الأصناف تحت الحد في رسالة واحدة.
 *
 * منفصل عن تنبيه الصنف الواحد عن قصد: التنبيه الفوري بيقول «حصل
 * دلوقتي»، والملخّص بيقول «الوضع العام الصبح ده» — ومحدش عايز خمستاشر
 * رسالة منفصلة.
 */
class DailyStockDigest extends Notification implements ShouldQueue
{
    use Queueable, RestrictsChannels;

    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(public readonly Collection $products) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // المستقبل اللي مش مستخدم (إيميل زيادة) بياخد الإيميل وخلاص
        if (! $notifiable instanceof User) {
            return $this->restrict(['mail']);
        }

        return $this->restrict(
            app(NotificationPreferences::class)->channelsFor($notifiable, 'daily_digest')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_digest',
            'title' => $this->title(),
            'body' => $this->summary(),
            'count' => $this->products->count(),
            'url' => '/inventory',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lines = $this->products
            ->take(20)
            ->map(fn ($product): string => '• '.$product->name.' ('.$product->sku.') — '
                .'الرصيد '.$this->quantity((float) $product->stock_balance, $product->unit)
                .'، حد الطلب '.$this->quantity((float) $product->min_stock, $product->unit))
            ->all();

        if ($this->products->count() > 20) {
            $lines[] = '… و'.($this->products->count() - 20).' صنف كمان.';
        }

        return (new MailMessage)
            ->subject($this->title())
            ->view('mail.notification', [
                'greeting' => $notifiable instanceof User
                    ? 'صباح الخير '.$notifiable->name
                    : 'صباح الخير',
                'title' => $this->title(),
                'lines' => [$this->summary(), ...$lines],
                'actionUrl' => url('/inventory'),
                'actionText' => 'افتح المخزون',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->summary(),
            'url' => '/inventory',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template' => config('notifications.whatsapp.templates.daily_digest'),
            'parameters' => [
                (string) $this->products->count(),
                $this->products->take(3)->pluck('name')->implode('، ') ?: '—',
            ],
        ];
    }

    private function title(): string
    {
        return 'ملخّص النواقص — '.$this->products->count().' صنف تحت حد الطلب';
    }

    private function summary(): string
    {
        $count = $this->products->count();

        return match (true) {
            $count === 1 => 'فيه صنف واحد تحت حد الطلب النهارده.',
            $count === 2 => 'فيه صنفين تحت حد الطلب النهارده.',
            $count <= 10 => 'فيه '.$count.' أصناف تحت حد الطلب النهارده.',
            default => 'فيه '.$count.' صنف تحت حد الطلب النهارده.',
        };
    }

    private function quantity(float $value, ?string $unit): string
    {
        $formatted = number_format($value, fmod($value, 1) === 0.0 ? 0 : 2, '.', ',');

        return $unit ? $formatted.' '.$unit : $formatted;
    }
}
