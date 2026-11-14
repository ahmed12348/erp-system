<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * فواتير الشراء (الاستيراد).
 *
 * الاتفاقية:
 *  - أسعار البنود وإجمالي الفاتورة بعملة المورد، و exchange_rate هو التحويل للجنيه.
 *  - مصاريف الاستيراد (شحن / جمارك / تخليص) بالجنيه المصري لأنها بتتدفع محلياً.
 *  - التكلفة الواصلة للصنف = (سعر الوحدة × سعر الصرف) + نصيبه من المصاريف،
 *    والمصاريف بتتوزّع بالتناسب مع قيمة كل بند مش بالتساوي على البنود.
 *  - stock_movements.unit_cost دايماً بالجنيه، و products.purchase_price
 *    بيتحدّث بآخر تكلفة واصلة.
 */
class PurchaseService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * @param  array{supplier_id: int, warehouse_id: int, date: string, currency: string, exchange_rate: float, shipping_cost?: float, customs_cost?: float, clearance_cost?: float, discount?: float, notes?: ?string, items: list<array{product_id: int, quantity: float, unit_price: float}>}  $data
     */
    public function createPurchase(array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($data, $user) {
            $supplier = Supplier::findOrFail($data['supplier_id']);
            $warehouseId = (int) $data['warehouse_id'];
            $rate = round((float) $data['exchange_rate'], 4);

            if ($rate <= 0) {
                throw ValidationException::withMessages([
                    'exchange_rate' => 'سعر الصرف لازم يكون أكبر من صفر.',
                ]);
            }

            $lines = $this->buildLines($data['items']);

            $subtotal = round(array_sum(array_column($lines, 'total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'الخصم ماينفعش يزيد عن إجمالي البنود.',
                ]);
            }

            $shipping = round((float) ($data['shipping_cost'] ?? 0), 2);
            $customs = round((float) ($data['customs_cost'] ?? 0), 2);
            $clearance = round((float) ($data['clearance_cost'] ?? 0), 2);
            $landedTotal = $shipping + $customs + $clearance;

            $invoice = Invoice::create([
                'type' => Invoice::TYPE_PURCHASE,
                'number' => $this->invoices->nextNumber(Invoice::TYPE_PURCHASE),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouseId,
                'user_id' => $user->id,
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? Carbon::parse($data['date'])->addDays(45)->toDateString(),
                'currency' => $data['currency'],
                'exchange_rate' => $rate,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0,
                'shipping_cost' => $shipping,
                'customs_cost' => $customs,
                'clearance_cost' => $clearance,
                'total' => round($subtotal - $discount, 2),
                'paid' => 0,
                'status' => Invoice::STATUS_CONFIRMED,
                'notes' => $data['notes'] ?? null,
            ]);

            // أساس التوزيع: قيمة البنود. لو الإجمالي صفر بنوزّع بالتساوي.
            $basis = $subtotal > 0 ? $subtotal : null;
            $allocated = 0.0;
            $lastIndex = count($lines) - 1;

            foreach ($lines as $index => $line) {
                $invoice->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'total' => $line['total'],
                ]);

                // آخر بند بياخد الباقي عشان مجموع التوزيع يساوي المصاريف بالظبط
                $share = $index === $lastIndex
                    ? round($landedTotal - $allocated, 2)
                    : round($landedTotal * ($basis ? $line['total'] / $basis : 1 / count($lines)), 2);

                $allocated += $share;

                // التكلفة الواصلة للوحدة بالجنيه
                $unitCost = round(
                    ($line['unit_price'] * $rate) + ($line['quantity'] > 0 ? $share / $line['quantity'] : 0),
                    2,
                );

                $this->recordMovement($invoice, $line['product'], $warehouseId, $line['quantity'], $unitCost);

                // آخر تكلفة شراء للصنف
                $line['product']->forceFill(['purchase_price' => $unitCost])->save();
            }

            return $invoice->fresh(['items', 'supplier']);
        });
    }

    /**
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
            $key = $product->id;

            // تجميع المكرر عشان التكلفة الواصلة تتحسب على الكمية الكلية
            if (isset($lines[$key])) {
                $lines[$key]['quantity'] += $quantity;
                $lines[$key]['total'] += round($quantity * $unitPrice, 2);

                continue;
            }

            $lines[$key] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }

        return array_values($lines);
    }

    private function recordMovement(Invoice $invoice, Product $product, int $warehouseId, float $quantity, float $unitCost): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,          // الشراء بيدخّل بضاعة — موجب
            'unit_cost' => $unitCost,         // بالجنيه، شامل المصاريف
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'date' => $invoice->date->toDateString(),
            'notes' => 'وارد بفاتورة مشتريات '.$invoice->number,
        ]);
    }
}
