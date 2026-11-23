<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * إنشاء الفواتير وحركات المخزون المقابلة لها.
 *
 * كل عملية جوه DB::transaction: يا إما الفاتورة وبنودها وحركات المخزون
 * بتتسجّل كلها، يا إما مفيش حاجة بتتسجّل خالص.
 *
 * رصيد التاجر مش عمود محفوظ — هو محسوب من (افتتاحي + مبيعات − مرتجعات − تحصيلات)،
 * فحفظ الفاتورة لوحده بيحدّث الرصيد تلقائياً من غير أي كتابة زيادة.
 */
class InvoiceService
{
    public const VAT_RATE = 0.14;

    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * فاتورة بيع بالجنيه المصري.
     *
     * @param  array{customer_id: int, warehouse_id: int, date: string, due_date?: ?string, discount?: float, notes?: ?string, items: list<array{product_id: int, quantity: float, unit_price: float, discount?: float}>}  $data
     */
    public function createSale(array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($data, $user) {
            $customer = Customer::findOrFail($data['customer_id']);
            $warehouseId = (int) $data['warehouse_id'];

            $lines = $this->buildLines($data['items']);
            $this->assertStockIsAvailable($lines, $warehouseId);

            $subtotal = round(array_sum(array_column($lines, 'total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'الخصم ماينفعش يزيد عن إجمالي البنود.',
                ]);
            }

            $taxable = $subtotal - $discount;
            $tax = round($taxable * self::VAT_RATE, 2);

            $invoice = Invoice::create([
                'type' => Invoice::TYPE_SALE,
                'number' => $this->nextNumber(Invoice::TYPE_SALE),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouseId,
                'user_id' => $user->id,
                // الفاتورة بتورث مندوب التاجر — من غير كده تقارير المندوبين
                // بتفضل أصفار على نظام جديد
                'sales_rep_id' => $customer->sales_rep_id,
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? Carbon::parse($data['date'])->addDays(30)->toDateString(),
                'currency' => 'EGP',
                'exchange_rate' => 1,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => round($taxable + $tax, 2),
                'paid' => 0,
                'status' => Invoice::STATUS_CONFIRMED,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => $line['discount'],
                    'total' => $line['total'],
                ]);

                // البيع بيطلّع بضاعة — الكمية سالبة
                $this->recordMovement(
                    $invoice,
                    $line['product'],
                    $warehouseId,
                    StockMovement::TYPE_OUT,
                    -$line['quantity'],
                    (float) $line['product']->purchase_price,
                    'صرف بفاتورة مبيعات '.$invoice->number,
                );
            }

            /*
             * فحص النواقص بعد ما المعاملة تخلص بنجاح — مش جواها.
             * التنبيه مايتبعتش على بيع اتلغى، والقراءة بتشوف الأرصدة
             * النهائية مش نص الحركة.
             */
            $productIds = array_map(fn (array $line): int => $line['product']->id, $lines);

            DB::afterCommit(fn () => app(StockAlertService::class)->check($productIds));

            return $invoice->fresh(['items', 'customer']);
        });
    }

    /**
     * تجهيز بنود الفاتورة مع تجميع المكرر.
     *
     * لو نفس الصنف اتكتب في أكتر من سطر، بنجمّعه في سطر واحد عشان فحص
     * الرصيد يبقى على إجمالي الكمية مش على كل سطر لوحده — من غير كده
     * ممكن سطرين كل واحد جوه الرصيد لكن مجموعهم أكبر منه.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'لازم تضيف صنف واحد على الأقل للفاتورة.',
            ]);
        }

        $products = Product::whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');
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
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'الكمية لازم تكون أكبر من صفر.',
                ]);
            }

            $unitPrice = round((float) $item['unit_price'], 2);
            $lineDiscount = round((float) ($item['discount'] ?? 0), 2);
            $gross = $quantity * $unitPrice;

            if ($lineDiscount > $gross) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount" => 'خصم البند ماينفعش يزيد عن قيمته.',
                ]);
            }

            $key = $product->id;

            if (isset($lines[$key])) {
                $lines[$key]['quantity'] += $quantity;
                $lines[$key]['discount'] += $lineDiscount;
                $lines[$key]['total'] += round($gross - $lineDiscount, 2);

                continue;
            }

            $lines[$key] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $lineDiscount,
                'total' => round($gross - $lineDiscount, 2),
            ];
        }

        return array_values($lines);
    }

    /**
     * رفض الفاتورة لو الرصيد مش كافي، برسالة بتقول الصنف والمتاح والمطلوب.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    private function assertStockIsAvailable(array $lines, int $warehouseId): void
    {
        $errors = [];

        foreach ($lines as $index => $line) {
            $available = $this->inventory->stockFor($line['product']->id, $warehouseId);

            if ($available + 1e-6 < $line['quantity']) {
                $errors["items.{$index}.quantity"] = sprintf(
                    'الرصيد مش كافي للصنف «%s» — المتاح %s %s والمطلوب %s %s.',
                    $line['product']->name,
                    $this->formatQuantity($available),
                    $line['product']->unit,
                    $this->formatQuantity($line['quantity']),
                    $line['product']->unit,
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function recordMovement(Invoice $invoice, Product $product, int $warehouseId, string $type, float $quantity, ?float $unitCost, string $notes): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'date' => $invoice->date->toDateString(),
            'notes' => $notes,
        ]);
    }

    /** ترقيم تسلسلي لكل نوع فاتورة في السنة الحالية */
    public function nextNumber(string $type): string
    {
        $prefix = match ($type) {
            Invoice::TYPE_SALE => 'S',
            Invoice::TYPE_PURCHASE => 'P',
            Invoice::TYPE_SALE_RETURN => 'SR',
            Invoice::TYPE_PURCHASE_RETURN => 'PR',
            default => 'INV',
        };

        $year = Carbon::today()->year;
        $stem = "{$prefix}-{$year}-";

        // lockForUpdate عشان طلبين في نفس اللحظة ما ياخدوش نفس الرقم
        $last = Invoice::query()
            ->where('type', $type)
            ->where('number', 'like', $stem.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last ? ((int) substr($last, strlen($stem))) + 1 : 1;

        return $stem.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
    }
}
