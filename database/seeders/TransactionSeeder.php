<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * حركة ٣ شهور: فواتير مشتريات (استيراد بعملات مختلفة) ثم مبيعات للتجار،
 * مرتجعات، تحصيلات ومدفوعات — مع حركات مخزون مقابلة لكل فاتورة.
 *
 * الاتفاقية المالية:
 *  - مبالغ الفاتورة وأسعار بنودها بعملة الفاتورة، و exchange_rate هو سعر التحويل للجنيه.
 *  - stock_movements.unit_cost دائماً بالجنيه المصري (التكلفة الواصلة).
 *  - المبيعات كلها بالجنيه المصري (exchange_rate = 1).
 */
class TransactionSeeder extends Seeder
{
    private const DAYS = 90;

    private const VAT_RATE = 0.14;

    /** محافظات يخدمها مخزن الإسكندرية */
    private const ALEX_CITIES = ['الإسكندرية', 'البحيرة', 'كفر الشيخ', 'مطروح', 'الغربية'];

    /** المورد => التصنيفات اللي بيوردها */
    private const SUPPLIER_CATEGORIES = [
        'جوانزو هونج فا للتجارة' => ['أجهزة منزلية'],
        'نينجبو صن رايز للأجهزة الكهربائية' => ['عدد وأدوات كهربائية'],
        'شنزن برايت واي للإضاءة' => ['إضاءة'],
        'إسطنبول الأناضول للمنسوجات' => ['مفروشات ومنسوجات'],
        'إزمير للأدوات المنزلية' => ['أدوات مطبخ', 'أدوات منزلية بلاستيك'],
    ];

    /** سعر صرف تقريبي للجنيه مع تذبذب بسيط على مدار الفترة */
    private const RATES = ['USD' => [47.90, 49.60], 'EUR' => [51.80, 54.10], 'TRY' => [1.36, 1.46]];

    private CarbonInterface $start;

    /** @var array<int, array<int, float>> [product_id][warehouse_id] => رصيد جاري */
    private array $stock = [];

    /** @var array<string, int> */
    private array $sequences = ['sale' => 0, 'purchase' => 0, 'sale_return' => 0];

    /** @var list<array<string, mixed>> */
    private array $itemRows = [];

    /** @var list<array<string, mixed>> */
    private array $movementRows = [];

    /** @var list<array<string, mixed>> */
    private array $paymentRows = [];

    public function run(): void
    {
        // عشان نقدر نكتب created_at/updated_at بتواريخ الفترة بدل تاريخ التشغيل
        Model::unguarded(fn () => $this->seedPeriod());
    }

    private function seedPeriod(): void
    {
        mt_srand(20261113); // بيانات عرض ثابتة عند إعادة التشغيل

        $this->start = Carbon::today()->subDays(self::DAYS);

        $users = User::pluck('id')->all();
        $warehouses = Warehouse::pluck('id', 'name')->all();
        $mainWarehouse = array_values($warehouses)[0];
        $altWarehouse = array_values($warehouses)[1];
        $products = Product::with('category')->get();
        $suppliers = Supplier::all();
        $customers = Customer::all();

        // ١. مشتريات افتتاحية: لكل مورد شحنة كبيرة للمخزن الرئيسي ثم شحنة أصغر للإسكندرية
        foreach ($suppliers as $i => $supplier) {
            $this->createPurchase($supplier, $products, $mainWarehouse, $this->start->copy()->addDays($i), $users, 1.0);
            $this->createPurchase($supplier, $products, $altWarehouse, $this->start->copy()->addDays(5 + $i), $users, 0.4);
        }

        // ٢. مبيعات ومشتريات تكميلية بالترتيب الزمني حتى يظل الرصيد منطقياً
        $events = [];

        for ($i = 0; $i < 8; $i++) {
            $events[] = ['purchase', $this->start->copy()->addDays(mt_rand(22, 82))];
        }

        for ($i = 0; $i < 115; $i++) {
            // كثافة أعلى في الأسابيع الأخيرة
            $day = (int) round(self::DAYS * sqrt(mt_rand(0, 10000) / 10000));
            $date = $this->start->copy()->addDays(max(6, min(self::DAYS, $day)));

            if ($date->isFriday()) {
                continue; // إجازة أسبوعية
            }

            $events[] = ['sale', $date];
        }

        usort($events, fn ($a, $b) => $a[1] <=> $b[1]);

        $sales = [];

        foreach ($events as [$kind, $date]) {
            if ($kind === 'purchase') {
                $supplier = $suppliers->random();
                $warehouse = mt_rand(1, 100) <= 70 ? $mainWarehouse : $altWarehouse;
                $this->createPurchase($supplier, $products, $warehouse, $date, $users, 0.55);

                continue;
            }

            $customer = $customers->random();
            $warehouse = in_array($customer->city, self::ALEX_CITIES, true) ? $altWarehouse : $mainWarehouse;

            if ($invoice = $this->createSale($customer, $products, $warehouse, $date, $users)) {
                $sales[] = $invoice;
            }
        }

        // ٣. مرتجعات مبيعات قليلة
        foreach (array_slice($sales, 10, 5) as $sale) {
            $this->createSaleReturn($sale, $users);
        }

        $this->flush();

        // ٤. ترحيل المدفوعات على الفواتير
        $this->applyPayments();

        // ٥. أصناف تحت حد الطلب
        $this->markLowStockProducts();
    }

    /** فاتورة مشتريات بعملة المورد */
    private function createPurchase(Supplier $supplier, $products, int $warehouseId, CarbonInterface $date, array $users, float $scale): void
    {
        $categories = self::SUPPLIER_CATEGORIES[$supplier->name];
        $pool = $products->filter(fn ($p) => in_array($p->category?->name, $categories, true));

        if ($pool->isEmpty()) {
            return;
        }

        $rate = $this->rateFor($supplier->currency, $date);
        $items = [];
        $subtotal = 0.0;

        foreach ($pool as $product) {
            $qty = (int) round($this->purchaseQty((float) $product->purchase_price) * $scale * (mt_rand(80, 125) / 100));

            if ($qty < 1) {
                continue;
            }

            // سعر المورد بعملته = التكلفة الواصلة بالجنيه ÷ سعر الصرف، مع تفاوت بسيط
            $unitPrice = round(((float) $product->purchase_price / $rate) * (mt_rand(92, 106) / 100), 2);
            $total = round($qty * $unitPrice, 2);
            $subtotal += $total;

            $items[] = ['product' => $product, 'qty' => $qty, 'unit_price' => $unitPrice, 'total' => $total];
        }

        if ($items === []) {
            return;
        }

        $discount = round($subtotal * (mt_rand(0, 30) / 1000), 2);
        $total = round($subtotal - $discount, 2);

        $invoice = Invoice::create([
            'type' => Invoice::TYPE_PURCHASE,
            'number' => $this->nextNumber('purchase'),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouseId,
            'user_id' => $users[array_rand($users)],
            'date' => $date,
            'due_date' => $date->copy()->addDays(45),
            'currency' => $supplier->currency,
            'exchange_rate' => $rate,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => 0,
            'total' => $total,
            'paid' => 0,
            'status' => Invoice::STATUS_CONFIRMED,
            'notes' => 'شحنة واردة من '.$supplier->country,
            'created_at' => $date->copy()->addHours(mt_rand(9, 16)),
            'updated_at' => $date->copy()->addHours(mt_rand(9, 16)),
        ]);

        foreach ($items as $item) {
            $this->addItem($invoice, $item);
            $this->addMovement($invoice, $item['product']->id, $warehouseId, StockMovement::TYPE_IN, $item['qty'], round($item['unit_price'] * $rate, 2), $date, 'وارد بفاتورة مشتريات '.$invoice->number);
        }

        $this->schedulePayments($invoice, Payment::TYPE_DISBURSEMENT, $supplier->id, $total, $date);
    }

    /** فاتورة مبيعات بالجنيه المصري — لا تتجاوز الرصيد المتاح */
    private function createSale(Customer $customer, $products, int $warehouseId, CarbonInterface $date, array $users): ?Invoice
    {
        $picked = $products->random(mt_rand(2, 6));
        $items = [];
        $subtotal = 0.0;

        foreach ($picked as $product) {
            $available = $this->available($product->id, $warehouseId);

            if ($available < 1) {
                continue;
            }

            $qty = min((int) $available, $this->saleQty((float) $product->purchase_price));

            if ($qty < 1) {
                continue;
            }

            $unitPrice = $product->priceFor($customer);
            $gross = $qty * $unitPrice;
            $discount = mt_rand(1, 100) <= 25 ? round($gross * (mt_rand(10, 50) / 1000), 2) : 0.0;
            $total = round($gross - $discount, 2);
            $subtotal += $total;

            $items[] = ['product' => $product, 'qty' => $qty, 'unit_price' => $unitPrice, 'discount' => $discount, 'total' => $total];
        }

        if ($items === []) {
            return null;
        }

        $tax = round($subtotal * self::VAT_RATE, 2);
        $total = round($subtotal + $tax, 2);

        $invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => $this->nextNumber('sale'),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouseId,
            'user_id' => $users[array_rand($users)],
            'date' => $date,
            'due_date' => $date->copy()->addDays(30),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => $tax,
            'total' => $total,
            'paid' => 0,
            'status' => Invoice::STATUS_CONFIRMED,
            'eta_status' => $this->etaStatus($date),
            'eta_uuid' => null,
            'created_at' => $date->copy()->addHours(mt_rand(9, 18)),
            'updated_at' => $date->copy()->addHours(mt_rand(9, 18)),
        ]);

        if ($invoice->eta_status !== null) {
            $invoice->forceFill(['eta_uuid' => strtoupper(bin2hex(random_bytes(13)))])->saveQuietly();
        }

        foreach ($items as $item) {
            $this->addItem($invoice, $item);
            $this->addMovement($invoice, $item['product']->id, $warehouseId, StockMovement::TYPE_OUT, -$item['qty'], (float) $item['product']->purchase_price, $date, 'صرف بفاتورة مبيعات '.$invoice->number);
        }

        $this->schedulePayments($invoice, Payment::TYPE_RECEIPT, $customer->id, $total, $date);

        return $invoice;
    }

    /** مرتجع مبيعات جزئي لفاتورة قائمة */
    private function createSaleReturn(Invoice $sale, array $users): void
    {
        $sourceItems = collect($this->itemRows)->where('invoice_id', $sale->id)->take(2);

        if ($sourceItems->isEmpty()) {
            return;
        }

        $date = $sale->date->copy()->addDays(mt_rand(3, 12));

        if ($date->isFuture()) {
            return;
        }

        $subtotal = 0.0;
        $lines = [];

        foreach ($sourceItems as $row) {
            $qty = max(1, (int) floor($row['quantity'] * (mt_rand(15, 40) / 100)));
            $total = round($qty * $row['unit_price'], 2);
            $subtotal += $total;
            $lines[] = ['product_id' => $row['product_id'], 'qty' => $qty, 'unit_price' => $row['unit_price'], 'total' => $total];
        }

        $tax = round($subtotal * self::VAT_RATE, 2);
        $total = round($subtotal + $tax, 2);

        $invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE_RETURN,
            'number' => $this->nextNumber('sale_return'),
            'customer_id' => $sale->customer_id,
            'warehouse_id' => $sale->warehouse_id,
            'user_id' => $users[array_rand($users)],
            'date' => $date,
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => $tax,
            'total' => $total,
            'paid' => 0,
            'status' => Invoice::STATUS_CONFIRMED,
            'notes' => 'مرتجع على الفاتورة '.$sale->number,
            'created_at' => $date->copy()->addHours(12),
            'updated_at' => $date->copy()->addHours(12),
        ]);

        foreach ($lines as $line) {
            $this->itemRows[] = [
                'invoice_id' => $invoice->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['unit_price'],
                'discount' => 0,
                'total' => $line['total'],
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->created_at,
            ];

            $this->addMovement($invoice, $line['product_id'], $sale->warehouse_id, StockMovement::TYPE_IN, $line['qty'], null, $date, 'ارتجاع بفاتورة '.$invoice->number);
        }
    }

    // ———————————————————————————— مساعدات ————————————————————————————

    private function addItem(Invoice $invoice, array $item): void
    {
        $this->itemRows[] = [
            'invoice_id' => $invoice->id,
            'product_id' => $item['product']->id,
            'quantity' => $item['qty'],
            'unit_price' => $item['unit_price'],
            'discount' => $item['discount'] ?? 0,
            'total' => $item['total'],
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->created_at,
        ];
    }

    private function addMovement(Invoice $invoice, int $productId, int $warehouseId, string $type, float $quantity, ?float $unitCost, CarbonInterface $date, string $notes): void
    {
        $this->stock[$productId][$warehouseId] = $this->available($productId, $warehouseId) + $quantity;

        $this->movementRows[] = [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'date' => $date->toDateString(),
            'notes' => $notes,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->created_at,
        ];
    }

    private function available(int $productId, int $warehouseId): float
    {
        return $this->stock[$productId][$warehouseId] ?? 0.0;
    }

    /** يوزّع مبلغ الفاتورة على دفعة أو دفعتين، وجزء من الفواتير يفضل مفتوحاً */
    private function schedulePayments(Invoice $invoice, string $type, int $partyId, float $total, CarbonInterface $date): void
    {
        $roll = mt_rand(1, 100);
        $methods = ['cash', 'bank', 'check'];

        $portions = match (true) {
            $roll <= 38 => [1.0],                                   // سداد كامل
            $roll <= 72 => [mt_rand(30, 70) / 100],                 // سداد جزئي
            $roll <= 82 => [0.4, mt_rand(20, 45) / 100],            // دفعتين
            default => [],                                          // لسه مفتوحة
        };

        foreach ($portions as $i => $portion) {
            $paidAt = $date->copy()->addDays(mt_rand(1, 25) + ($i * 15));

            if ($paidAt->isFuture()) {
                continue;
            }

            $this->paymentRows[] = [
                'type' => $type,
                'customer_id' => $type === Payment::TYPE_RECEIPT ? $partyId : null,
                'supplier_id' => $type === Payment::TYPE_DISBURSEMENT ? $partyId : null,
                'invoice_id' => $invoice->id,
                'amount' => round($total * $portion, 2),
                'method' => $methods[array_rand($methods)],
                'date' => $paidAt->toDateString(),
                'reference' => null,
                'user_id' => $invoice->user_id,
                'notes' => null,
                'created_at' => $paidAt->copy()->addHours(mt_rand(10, 17)),
                'updated_at' => $paidAt->copy()->addHours(mt_rand(10, 17)),
            ];
        }
    }

    private function flush(): void
    {
        foreach (array_chunk($this->itemRows, 500) as $chunk) {
            DB::table('invoice_items')->insert($chunk);
        }

        foreach (array_chunk($this->movementRows, 500) as $chunk) {
            DB::table('stock_movements')->insert($chunk);
        }

        foreach (array_chunk($this->paymentRows, 500) as $chunk) {
            DB::table('payments')->insert($chunk);
        }
    }

    /** تحديث عمود paid على كل فاتورة من واقع مدفوعاتها */
    private function applyPayments(): void
    {
        DB::statement('
            UPDATE invoices i
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS total_paid
                FROM payments
                WHERE invoice_id IS NOT NULL
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            SET i.paid = COALESCE(p.total_paid, 0)
        ');

        // رقم مرجعي للشيكات والتحويلات
        Payment::whereIn('method', ['check', 'bank'])->eachById(function (Payment $payment) {
            $prefix = $payment->method === 'check' ? 'شيك ' : 'تحويل ';
            $payment->forceFill(['reference' => $prefix.mt_rand(100000, 999999)])->saveQuietly();
        });
    }

    /** يرفع حد الطلب لبعض الأصناف سريعة الحركة حتى تظهر تحت الحد الأدنى */
    private function markLowStockProducts(): void
    {
        $balances = DB::table('stock_movements')
            ->selectRaw('product_id, SUM(quantity) AS qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $targets = Product::inRandomOrder()->limit(6)->pluck('id');

        foreach ($targets as $productId) {
            $qty = (float) ($balances[$productId] ?? 0);
            Product::whereKey($productId)->update(['min_stock' => max(5, ceil($qty * 1.35) + 10)]);
        }
    }

    private function nextNumber(string $type): string
    {
        $prefix = ['sale' => 'S', 'purchase' => 'P', 'sale_return' => 'SR'][$type];

        return sprintf('%s-%d-%05d', $prefix, Carbon::today()->year, ++$this->sequences[$type]);
    }

    private function rateFor(string $currency, CarbonInterface $date): float
    {
        [$from, $to] = self::RATES[$currency];
        $progress = $this->start->diffInDays($date) / self::DAYS;

        return round($from + (($to - $from) * $progress) + (mt_rand(-20, 20) / 1000), 4);
    }

    private function purchaseQty(float $cost): int
    {
        return match (true) {
            $cost < 100 => mt_rand(900, 2400),
            $cost < 500 => mt_rand(220, 650),
            $cost < 1500 => mt_rand(90, 260),
            default => mt_rand(30, 90),
        };
    }

    private function saleQty(float $cost): int
    {
        return match (true) {
            $cost < 100 => mt_rand(40, 260),
            $cost < 500 => mt_rand(10, 60),
            $cost < 1500 => mt_rand(3, 18),
            default => mt_rand(1, 7),
        };
    }

    /** الفواتير القديمة اتبعتت للمنظومة، الأحدث لسه */
    private function etaStatus(CarbonInterface $date): ?string
    {
        if ($date->diffInDays(Carbon::today()) < 5) {
            return mt_rand(1, 100) <= 35 ? 'submitted' : null;
        }

        return match (true) {
            mt_rand(1, 100) <= 88 => 'valid',
            mt_rand(1, 100) <= 60 => 'submitted',
            default => 'rejected',
        };
    }
}
