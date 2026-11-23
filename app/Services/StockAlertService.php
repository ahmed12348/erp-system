<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAlertState;
use App\Notifications\Erp\DailyStockDigest;
use App\Notifications\Erp\LowStockAlert;
use Illuminate\Support\Facades\Notification;

/**
 * تنبيهات المخزون — بتتبعت عند **العبور** بس.
 *
 * ده بيت القصيد: لو بعتنا تنبيه كل مرة صنف ناقص يتباع، صاحب المحل
 * هيتقفل عليه عشرين رسالة في اليوم وهيقفل التنبيهات كلها. فبنفتكر
 * حالة كل صنف، والتنبيه بيروح مرة واحدة لما الرصيد ينزل تحت الحد،
 * وما بيترجعش غير لما يطلع فوق الحد تاني وينزل من جديد.
 *
 * نفس الحكاية مع «خلص» — تنبيه منفصل أول ما يوصل صفر.
 */
class StockAlertService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly NotificationPreferences $preferences,
    ) {}

    /**
     * بيراجع حالة أصناف معيّنة بعد حركة مخزون.
     *
     * @param  array<int, int>  $productIds
     */
    public function check(array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $products = Product::whereIn('id', array_unique($productIds))->get();

        foreach ($products as $product) {
            $this->evaluate($product);
        }
    }

    /**
     * بيبعت ملخّص النواقص اليومي.
     *
     * بيرجع عدد المستخدمين اللي وصلهم — الأمر بيطبعه.
     */
    public function sendDigest(): int
    {
        $products = $this->inventory->lowStockProducts();

        if ($products->isEmpty()) {
            return 0;
        }

        $recipients = $this->preferences->recipientsFor('daily_digest');

        if ($recipients->isEmpty()) {
            return 0;
        }

        Notification::send(
            $recipients,
            new DailyStockDigest($products),
        );

        return $recipients->count();
    }

    /**
     * بيقارن حالة الصنف دلوقتي بآخر حالة متسجّلة، وبيبعت لو اتغيّرت.
     */
    private function evaluate(Product $product): void
    {
        $balance = $this->inventory->stockFor($product->id);
        $minimum = (float) $product->min_stock;

        $isOut = $balance <= 0;
        // الصنف اللي حد طلبه صفر مالوش تنبيه نقص — بس لسه بيتنبّه لما يخلص
        $isBelow = $minimum > 0 && $balance < $minimum;

        $state = StockAlertState::firstOrNew(['product_id' => $product->id]);

        $crossedOut = $isOut && ! $state->out_of_stock;
        $crossedBelow = $isBelow && ! $state->below_minimum;

        // الحالة بتتحدّث دايمًا، حتى لو مفيش تنبيه — ده اللي بيسمح
        // بالتنبيه تاني لما الرصيد يرجع ينزل بعد ما يتعبّى
        $state->fill([
            'below_minimum' => $isBelow,
            'out_of_stock' => $isOut,
        ]);

        if (! $crossedOut && ! $crossedBelow) {
            $state->save();

            return;
        }

        $state->notified_at = now();
        $state->save();

        /*
         * «خلص» بيكسب على «قرب يخلص»: الصنف اللي رصيده صفر هو تحت
         * الحد كمان، ومحدش عايز رسالتين على نفس الحاجة.
         */
        $notification = new LowStockAlert($product, $balance, outOfStock: $crossedOut);
        $type = $crossedOut ? 'out_of_stock' : 'low_stock';

        $recipients = $this->preferences->recipientsFor($type);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }
    }
}
