<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * المرتجعات — بيع وشراء.
 *
 * المرتجع فاتورة بنوع تاني (`sale_return` / `purchase_return`) مربوطة
 * بالأصلية. كله جوه معاملة واحدة: يا إما المستند وبنوده وحركات المخزون
 * والسند بيتسجّلوا كلهم، يا إما مفيش حاجة بتتسجّل خالص.
 *
 * **الأثر:**
 *
 *  - **المخزون**: مرتجع البيع بيرجّع البضاعة للمخزن، ومرتجع الشراء
 *    بيطلّعها. الاتنين **بتكلفة الصنف وقت الفاتورة الأصلية** مش بالتكلفة
 *    الحالية — وإلا المرتجع بيغيّر قيمة المخزون من غير ما يكون فيه بيع
 *    ولا شرا.
 *  - **رصيد الطرف**: محسوب أصلاً من (مبيعات − مرتجعات − تحصيلات)، فحفظ
 *    المرتجع لوحده بيقلّل الرصيد من غير أي كتابة زيادة.
 *  - **عمولة المندوب**: العمولة بتتحسب على المحصّل، والمرتجع النقدي
 *    بيسجّل سند صرف منسوب لنفس المندوب — فبيقلّل المحصّل وبالتالي
 *    العمولة. المرتجع من غير فلوس بيقلّل صافي المبيعات.
 *  - **الخزينة**: الرد النقدي اختياري؛ لو اتعمل بيتسجّل سند في الخزينة.
 */
class ReturnService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Invoice>
     */
    public function query(array $filters): Builder
    {
        $type = ($filters['type'] ?? null) === Invoice::TYPE_PURCHASE_RETURN
            ? Invoice::TYPE_PURCHASE_RETURN
            : Invoice::TYPE_SALE_RETURN;

        return Invoice::query()
            ->where('type', $type)
            ->with(['customer:id,name', 'supplier:id,name', 'warehouse:id,name', 'originalInvoice:id,number'])
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('originalInvoice', fn (Builder $o) => $o->where('number', 'like', $term));
                })
            )
            ->when(
                filled($filters['reason'] ?? null),
                fn (Builder $query) => $query->where('return_reason', $filters['reason'])
            )
            ->when(
                ($filters['orphan'] ?? null) === '1',
                fn (Builder $query) => $query->where('is_orphan_return', true)
            )
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $query) => $query->where('date', '>=', Carbon::parse($filters['from'])->toDateString())
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $query) => $query->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString())
            )
            ->latest('date')
            ->latest('id');
    }

    /**
     * البنود المتاحة للمرتجع على فاتورة — ومعاها المرتجع منها قبل كده.
     *
     * السقف = المباع ناقص المرتجع، مش المباع. من غير الطرح ده ينفع
     * ترجّع نفس الكرتونة مرتين والمخزون يطلع أكتر من اللي اتشحن.
     *
     * @return list<array<string, mixed>>
     */
    public function returnableLines(Invoice $invoice): array
    {
        $invoice->loadMissing('items.product:id,sku,name,unit');

        $returned = $this->returnedQuantities($invoice);

        return $invoice->items->map(function (InvoiceItem $item) use ($returned): array {
            $already = $returned[$item->product_id] ?? 0.0;
            $sold = (float) $item->quantity;

            return [
                'productId' => $item->product_id,
                'sku' => $item->product?->sku,
                'name' => $item->product?->name,
                'unit' => $item->product?->unit,
                'unitPrice' => (float) $item->unit_price,
                'sold' => $sold,
                'returned' => $already,
                // اللي لسه ينفع يترجّع
                'returnable' => round(max($sold - $already, 0), 3),
            ];
        })->all();
    }

    /**
     * الكميات المرتجعة لكل صنف على فاتورة.
     *
     * @return array<int, float>
     */
    public function returnedQuantities(Invoice $invoice): array
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.original_invoice_id', $invoice->id)
            ->where('invoices.status', Invoice::STATUS_CONFIRMED)
            ->groupBy('invoice_items.product_id')
            ->selectRaw('invoice_items.product_id as product_id, sum(invoice_items.quantity) as qty')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->product_id => (float) $row->qty])
            ->all();
    }

    /**
     * مرتجع على فاتورة موجودة.
     *
     * @param  array{date: string, return_reason: string, notes?: ?string, refund?: ?array{amount: float, method: string}, items: list<array{product_id: int, quantity: float}>}  $data
     */
    public function createFor(Invoice $original, array $data, User $user): Invoice
    {
        if ($original->status !== Invoice::STATUS_CONFIRMED) {
            throw ValidationException::withMessages([
                'original' => 'المرتجع بيتعمل على فاتورة مؤكّدة بس.',
            ]);
        }

        if ($original->isReturn()) {
            throw ValidationException::withMessages([
                'original' => 'ماينفعش تعمل مرتجع على مرتجع.',
            ]);
        }

        return DB::transaction(function () use ($original, $data, $user) {
            /*
             * قفل الفاتورة الأصلية: مرتجعين في نفس اللحظة على نفس
             * الفاتورة كانوا ممكن الاتنين يشوفوا نفس «المتاح للمرتجع»
             * ويعدّوا مع بعض، والمجموع يطلع أكبر من المباع.
             */
            $original = Invoice::query()->lockForUpdate()->findOrFail($original->id);

            $lines = $this->buildLines($data['items'], $original);

            return $this->persist($original, $lines, $data, $user, orphan: false);
        });
    }

    /**
     * مرتجع من غير فاتورة أصلية — للمدير بس.
     *
     * بيحصل: بضاعة راجعة من زمان ومحدش لاقي ورقتها. السعر هنا بيتكتب
     * بالإيد لأن مفيش فاتورة نرجع لها، والمستند بيتعلّم `is_orphan_return`
     * عشان المراجعة تعرف تفرزه.
     *
     * @param  array{type: string, customer_id?: ?int, supplier_id?: ?int, warehouse_id: int, date: string, return_reason: string, notes?: ?string, refund?: ?array{amount: float, method: string}, items: list<array{product_id: int, quantity: float, unit_price: float}>}  $data
     */
    public function createOrphan(array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($data, $user) {
            $lines = $this->buildLines($data['items'], null);

            $shell = new Invoice([
                'type' => $data['type'] === Invoice::TYPE_PURCHASE_RETURN
                    ? Invoice::TYPE_PURCHASE
                    : Invoice::TYPE_SALE,
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'warehouse_id' => (int) $data['warehouse_id'],
                'currency' => 'EGP',
                'exchange_rate' => 1,
            ]);

            return $this->persist($shell, $lines, $data, $user, orphan: true);
        });
    }

    /**
     * بيكتب المستند وبنوده وحركات المخزون والسند.
     *
     * `$original` ساعات بيبقى فاتورة حقيقية وساعات قوقعة فيها الطرف
     * والمخزن بس (المرتجع اليتيم) — الاتنين بيوصلوا لنفس الكود عشان
     * مايبقاش فيه مسارين للكتابة يفرقوا عن بعض.
     *
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $data
     */
    private function persist(Invoice $original, array $lines, array $data, User $user, bool $orphan): Invoice
    {
        $isSaleReturn = in_array($original->type, [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN], true);
        $type = $isSaleReturn ? Invoice::TYPE_SALE_RETURN : Invoice::TYPE_PURCHASE_RETURN;
        $warehouseId = (int) $original->warehouse_id;

        $subtotal = round(array_sum(array_column($lines, 'total')), 2);

        /*
         * الضريبة بنفس نسبة الفاتورة الأصلية مش بالنسبة الحالية.
         *
         * لو النسبة اتغيّرت بين البيع والمرتجع، حساب المرتجع بالنسبة
         * الجديدة بيسيب فرق على التاجر محدش يعرف يفسّره.
         */
        $taxRate = $orphan || (float) $original->subtotal <= 0
            ? InvoiceService::VAT_RATE
            : round((float) $original->tax / (float) $original->subtotal, 6);

        $tax = round($subtotal * $taxRate, 2);

        $invoice = Invoice::create([
            'type' => $type,
            'number' => $this->invoices->nextNumber($type),
            'original_invoice_id' => $orphan ? null : $original->id,
            'customer_id' => $original->customer_id,
            'supplier_id' => $original->supplier_id,
            'warehouse_id' => $warehouseId,
            'user_id' => $user->id,
            // المندوب بيتورّث من الفاتورة الأصلية عشان العمولة ترجع عليه هو
            'sales_rep_id' => $original->sales_rep_id,
            'date' => $data['date'],
            'currency' => $original->currency ?: 'EGP',
            'exchange_rate' => $original->exchange_rate ?: 1,
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'paid' => 0,
            'status' => Invoice::STATUS_CONFIRMED,
            'notes' => $data['notes'] ?? null,
            'return_reason' => $data['return_reason'],
            'is_orphan_return' => $orphan,
        ]);

        foreach ($lines as $line) {
            $invoice->items()->create([
                'product_id' => $line['product']->id,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount' => 0,
                'total' => $line['total'],
            ]);

            /*
             * مرتجع البيع بيرجّع البضاعة (موجب)، ومرتجع الشراء
             * بيطلّعها (سالب).
             */
            $quantity = $isSaleReturn ? $line['quantity'] : -$line['quantity'];

            StockMovement::create([
                'product_id' => $line['product']->id,
                'warehouse_id' => $warehouseId,
                'type' => $isSaleReturn ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT,
                'quantity' => $quantity,
                // التكلفة وقت الفاتورة الأصلية، مش الحالية
                'unit_cost' => $line['cost'],
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'user_id' => $user->id,
                'date' => $invoice->date->toDateString(),
                'notes' => ($isSaleReturn ? 'مرتجع مبيعات ' : 'مرتجع مشتريات ').$invoice->number,
            ]);
        }

        if (! $isSaleReturn) {
            $this->assertStockCoversPurchaseReturn($lines, $warehouseId, $invoice);
        }

        $refund = $this->recordRefund($invoice, $data['refund'] ?? null, $isSaleReturn, $user);

        $invoice->recordActivity('returned', array_filter([
            'number' => $invoice->number,
            'original' => $orphan ? null : $original->number,
            'reason' => $invoice->returnReasonLabel(),
            'total' => (float) $invoice->total,
            'refund' => $refund ? (float) $refund->amount : null,
            'orphan' => $orphan ?: null,
        ], fn ($value) => $value !== null));

        return $invoice->fresh(['items.product', 'customer', 'supplier', 'originalInvoice']);
    }

    /**
     * الرد النقدي — اختياري.
     *
     * مرتجع البيع بيطلّع فلوس من الخزينة (صرف للتاجر)، ومرتجع الشراء
     * بيدخّل فلوس (المورد بيرد). السند منسوب لنفس المندوب عشان العمولة
     * المحسوبة على المحصّل ترجع هي كمان.
     *
     * @param  array{amount: float, method: string}|null  $refund
     */
    private function recordRefund(Invoice $invoice, ?array $refund, bool $isSaleReturn, User $user): ?Payment
    {
        $amount = round((float) ($refund['amount'] ?? 0), 2);

        if ($amount <= 0) {
            return null;
        }

        if ($amount > (float) $invoice->total + 1e-6) {
            throw ValidationException::withMessages([
                'refund.amount' => 'المبلغ المردود ماينفعش يزيد عن قيمة المرتجع.',
            ]);
        }

        return Payment::create([
            // مرتجع بيع = فلوس خارجة، مرتجع شراء = فلوس داخلة
            'type' => $isSaleReturn ? Payment::TYPE_DISBURSEMENT : Payment::TYPE_RECEIPT,
            'customer_id' => $invoice->customer_id,
            'supplier_id' => $invoice->supplier_id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'currency' => $invoice->currency,
            'exchange_rate' => $invoice->exchange_rate,
            'method' => $refund['method'] ?? 'cash',
            'date' => $invoice->date->toDateString(),
            'reference' => 'رد نقدي — '.$invoice->number,
            'user_id' => $user->id,
            'sales_rep_id' => $invoice->sales_rep_id,
            'notes' => 'رد نقدي على '.$invoice->number,
        ]);
    }

    /**
     * مرتجع الشراء بيطلّع بضاعة — لازم تكون موجودة.
     *
     * الفحص بعد الكتابة عشان يحسب الرصيد بعد حركات المرتجع نفسها؛ لو
     * طلع بالسالب المعاملة بترجع كلها.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    private function assertStockCoversPurchaseReturn(array $lines, int $warehouseId, Invoice $invoice): void
    {
        $errors = [];

        foreach ($lines as $index => $line) {
            $balance = $this->inventory->stockFor($line['product']->id, $warehouseId);

            if ($balance < -1e-6) {
                $errors["items.{$index}.quantity"] = sprintf(
                    'الرصيد مش كافي لمرتجع «%s» — المخزن هيبقى بالسالب.',
                    $line['product']->name,
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * تجهيز البنود مع تجميع المكرر وفحص السقف.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $items, ?Invoice $original): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'اختار بند واحد على الأقل للمرتجع.',
            ]);
        }

        $products = Product::whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');
        $returnable = $original ? collect($this->returnableLines($original))->keyBy('productId') : null;
        $originalItems = $original?->items->keyBy('product_id');

        $lines = [];

        foreach ($items as $index => $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'الصنف المختار مش موجود.',
                ]);
            }

            $quantity = round((float) $item['quantity'], 3);

            if ($quantity <= 0) {
                continue;
            }

            if ($returnable !== null) {
                $line = $returnable->get($product->id);

                if (! $line) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'الصنف ده مش في الفاتورة الأصلية.',
                    ]);
                }

                if ($quantity > $line['returnable'] + 1e-6) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => sprintf(
                            'أقصى كمية ترجع من «%s» هي %s %s — المباع %s والمرتجع قبل كده %s.',
                            $product->name,
                            $this->formatQuantity($line['returnable']),
                            $product->unit,
                            $this->formatQuantity($line['sold']),
                            $this->formatQuantity($line['returned']),
                        ),
                    ]);
                }
            }

            // السعر من الفاتورة الأصلية — المرتجع بيرجّع بنفس اللي اتباع بيه
            $unitPrice = $originalItems?->get($product->id)
                ? (float) $originalItems->get($product->id)->unit_price
                : round((float) ($item['unit_price'] ?? 0), 2);

            $key = $product->id;

            if (isset($lines[$key])) {
                $lines[$key]['quantity'] += $quantity;
                $lines[$key]['total'] = round($lines[$key]['quantity'] * $unitPrice, 2);

                continue;
            }

            $lines[$key] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
                // تكلفة الصنف وقت الفاتورة الأصلية
                'cost' => $this->costAtOriginal($product, $original),
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'اختار بند واحد على الأقل للمرتجع.',
            ]);
        }

        return array_values($lines);
    }

    /**
     * تكلفة الصنف زي ما اتسجّلت على الفاتورة الأصلية.
     *
     * المرتجع لازم يرجّع البضاعة بنفس التكلفة اللي خرجت بيها — لو رجّعناها
     * بتكلفة النهارده، قيمة المخزون بتتغيّر من غير ما يكون فيه بيع ولا
     * شرا، والفرق ده بيظهر في تقييم المخزون كربح أو خسارة وهمية.
     */
    private function costAtOriginal(Product $product, ?Invoice $original): float
    {
        $cost = $original
            ? DB::table('stock_movements')
                ->where('reference_type', Invoice::class)
                ->where('reference_id', $original->id)
                ->where('product_id', $product->id)
                ->value('unit_cost')
            : null;

        return (float) ($cost ?? $product->purchase_price);
    }

    /** الأطراف المتاحة للمرتجع اليتيم */
    public function orphanParties(string $type): array
    {
        return $type === Invoice::TYPE_PURCHASE_RETURN
            ? Supplier::active()->orderBy('name')->get(['id', 'name'])->all()
            : Customer::active()->orderBy('name')->get(['id', 'name'])->all();
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
    }
}
