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
use Illuminate\Support\Facades\DB;

/**
 * صنف نزل تحت حد الطلب، أو خلص خالص.
 *
 * بيتبعت **مرة واحدة عند النزول** — مش مع كل بيعة. `StockAlertService`
 * هو اللي بيمسك الحالة ويقرّر إمتى يستاهل تنبيه.
 */
class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable, RestrictsChannels;

    public function __construct(
        public readonly Product $product,
        public readonly float $balance,
        public readonly bool $outOfStock = false,
    ) {}

    public function type(): string
    {
        return $this->outOfStock ? 'out_of_stock' : 'low_stock';
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        /*
         * المستقبل اللي مش مستخدم (إيميل زيادة) مالوش تفضيلات نقراها —
         * بياخد الإيميل وخلاص.
         */
        if (! $notifiable instanceof User) {
            return $this->restrict(['mail']);
        }

        return $this->restrict(
            app(NotificationPreferences::class)->channelsFor($notifiable, $this->type())
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'body' => $this->body(),
            'productId' => $this->product->id,
            'sku' => $this->product->sku,
            // الجرس بيودّي على صفحة الصنف
            'url' => '/inventory/'.$this->product->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->view('mail.notification', [
                // المستقبل الزيادة مالوش اسم في النظام
                'greeting' => $notifiable instanceof User
                    ? 'أهلاً '.$notifiable->name
                    : 'أهلاً',
                'title' => $this->title(),
                'lines' => [
                    $this->body(),
                    'حد الطلب للصنف ده: '.$this->quantity((float) $this->product->min_stock).'.',
                    // المخازن — اللي بيقرا الإيميل عايز يعرف الناقص فين
                    ...$this->warehouseLines(),
                ],
                'actionUrl' => url('/inventory/'.$this->product->id),
                'actionText' => 'افتح صفحة الصنف',
            ]);
    }

    /**
     * رصيد الصنف في كل مخزن.
     *
     * الرصيد الإجمالي لوحده مابيقولش فين الناقص — وعلى مخزنين، «باقي
     * ٣» ممكن تكون ٣ في الإسكندرية وصفر في الرئيسي.
     *
     * @return array<int, string>
     */
    private function warehouseLines(): array
    {
        $rows = DB::table('stock_movements')
            ->join('warehouses', 'warehouses.id', '=', 'stock_movements.warehouse_id')
            ->where('stock_movements.product_id', $this->product->id)
            ->groupBy('warehouses.id', 'warehouses.name')
            ->havingRaw('sum(stock_movements.quantity) <> 0')
            ->selectRaw('warehouses.name, sum(stock_movements.quantity) as qty')
            ->orderBy('warehouses.name')
            ->get();

        if ($rows->isEmpty()) {
            return ['مفيش رصيد في أي مخزن.'];
        }

        return $rows->map(
            fn ($row): string => 'مخزن '.$row->name.': '.$this->quantity((float) $row->qty)
        )->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => '/inventory/'.$this->product->id,
        ];
    }

    /**
     * رسالة واتساب — قالب معتمد مش نص حر.
     *
     * @return array<string, mixed>
     */
    public function toWhatsApp(object $notifiable): array
    {
        return [
            'template' => config('notifications.whatsapp.templates.low_stock'),
            'parameters' => [
                $this->product->name,
                $this->quantity($this->balance),
                $this->quantity((float) $this->product->min_stock),
            ],
        ];
    }

    private function title(): string
    {
        return $this->outOfStock
            ? 'صنف خلص: '.$this->product->name
            : 'صنف قرب يخلص: '.$this->product->name;
    }

    private function body(): string
    {
        if ($this->outOfStock) {
            return 'رصيد «'.$this->product->name.'» ('.$this->product->sku.') وصل صفر.';
        }

        return 'رصيد «'.$this->product->name.'» ('.$this->product->sku.') بقى '
            .$this->quantity($this->balance).' — تحت حد الطلب.';
    }

    private function quantity(float $value): string
    {
        $formatted = number_format($value, fmod($value, 1) === 0.0 ? 0 : 2, '.', ',');

        return $formatted.' '.$this->product->unit;
    }
}
