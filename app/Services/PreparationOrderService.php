<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PreparationOrder;
use App\Models\PreparationOrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * طلبات التحضير — فتح، تحضير، إلغاء.
 *
 * **مفيش أسعار في الملف ده خالص.** لا في القراءة ولا في الكتابة. الورقة
 * بتروح لعامل المخزن، ومحدش في المخزن محتاج يعرف التاجر بيشتري بكام.
 * السعر بيدخل الصورة في خطوة واحدة بس: وقت تحويل الطلب لفاتورة بيع،
 * وساعتها بيتحسب من شريحة التاجر زي أي فاتورة عادية.
 *
 * الحجز: الطلب المفتوح أو المحضّر بيخصم من «المتاح» في النظام كله من
 * غير ما يعمل حركة مخزون — البضاعة لسه على الرف. `InventoryService`
 * هو اللي بيحسب ده.
 */
class PreparationOrderService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, PreparationOrder>
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<PreparationOrder>
     */
    public function query(array $filters): Builder
    {
        return PreparationOrder::query()
            ->with(['customer:id,name', 'warehouse:id,name', 'user:id,name'])
            ->withCount('items')
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', $term));
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['customer_id'] ?? null),
                fn (Builder $query) => $query->where('customer_id', $filters['customer_id'])
            )
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id'])
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
     * طلب جديد — بيحجز الكميات على طول.
     *
     * @param  array{customer_id: int, warehouse_id: int, date: string, notes?: ?string, items: list<array{product_id: int, quantity: float}>}  $data
     */
    public function create(array $data, User $user): PreparationOrder
    {
        return DB::transaction(function () use ($data, $user) {
            $customer = Customer::findOrFail($data['customer_id']);
            $warehouseId = (int) $data['warehouse_id'];

            $lines = $this->buildLines($data['items']);
            $this->assertAvailable($lines, $warehouseId);

            $order = PreparationOrder::create([
                'number' => $this->nextNumber(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouseId,
                'user_id' => $user->id,
                'date' => $data['date'],
                'status' => PreparationOrder::STATUS_OPEN,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                ]);
            }

            return $order->fresh(['items.product', 'customer', 'warehouse']);
        });
    }

    /**
     * المخزن جهّز الطلب — وممكن يكون عدّل الكميات.
     *
     * الكمية المحضّرة بتتسجّل في عمود لوحدها، فالمطلوب الأصلي بيفضل
     * مكتوب. أول سؤال لما حد يختلف على كمية هو «هو طلب كام وإحنا
     * طلّعنا كام؟» — ولو دسنا على المطلوب، السؤال ده مابقاش ليه إجابة.
     *
     * @param  array<int, array{id: int, prepared_quantity: float}>  $items
     */
    public function markPrepared(PreparationOrder $order, array $items, User $user): PreparationOrder
    {
        $this->assertEditable($order);

        return DB::transaction(function () use ($order, $items, $user) {
            $adjusted = [];

            foreach ($items as $row) {
                $item = $order->items()->with('product')->findOrFail($row['id']);
                $quantity = round((float) $row['prepared_quantity'], 3);

                if ($quantity < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'الكمية المحضّرة ماينفعش تكون بالسالب.',
                    ]);
                }

                /*
                 * الزيادة عن المطلوب لازم تعدّي على فحص المتاح — المخزن
                 * ممكن يزوّد كمية، بس مش لحد ما يحجز بضاعة مش موجودة.
                 */
                if ($quantity > $item->effectiveQuantity()) {
                    $available = $this->inventory->availableFor(
                        $item->product_id,
                        $order->warehouse_id,
                        $order->id,
                    );

                    if ($available + 1e-6 < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => sprintf(
                                'المتاح من «%s» %s %s بس.',
                                $item->product?->name,
                                $this->formatQuantity($available),
                                $item->product?->unit,
                            ),
                        ]);
                    }
                }

                if (abs($quantity - $item->effectiveQuantity()) > 1e-6) {
                    $adjusted[$item->product?->name ?? (string) $item->product_id] = $quantity;
                }

                $item->update(['prepared_quantity' => $quantity]);
            }

            $order->update([
                'status' => PreparationOrder::STATUS_PREPARED,
                'prepared_at' => now(),
            ]);

            $order->recordActivity('prepared', [
                'by' => $user->name,
                // التعديلات بتتسجّل بالاسم والكمية عشان تبقى مقروءة في السجل
                'adjusted' => $adjusted,
            ]);

            return $order->fresh(['items.product']);
        });
    }

    /** إلغاء — بيفكّ الحجز من غير أي حركة مخزون */
    public function cancel(PreparationOrder $order, User $user): PreparationOrder
    {
        $this->assertEditable($order);

        $order->update([
            'status' => PreparationOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $order->recordActivity('cancelled', ['by' => $user->name]);

        return $order->fresh();
    }

    /**
     * بنود الطلب جاهزة لفورم فاتورة البيع — وده المكان الوحيد اللي
     * بيدخل فيه سعر.
     *
     * السعر من شريحة التاجر، زي ما الفورم بيعمل بالظبط لما تختار صنف
     * بإيدك — عشان الفاتورة اللي جاية من طلب تطلع بنفس أرقام الفاتورة
     * اللي اتكتبت بالإيد.
     *
     * @return list<array<string, mixed>>
     */
    public function invoiceLines(PreparationOrder $order): array
    {
        $order->loadMissing(['items.product', 'customer']);
        $tier = $order->customer?->price_tier;

        return $order->items->map(fn (PreparationOrderItem $item): array => [
            'product_id' => $item->product_id,
            'quantity' => $item->effectiveQuantity(),
            'unit_price' => $this->tierPrice($item->product, $tier),
        ])->all();
    }

    /** ترقيم تسلسلي للسنة الحالية — PR-2026-00001 */
    public function nextNumber(): string
    {
        $stem = 'PR-'.Carbon::today()->year.'-';

        // lockForUpdate عشان طلبين في نفس اللحظة ما ياخدوش نفس الرقم
        $last = PreparationOrder::query()
            ->where('number', 'like', $stem.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last ? ((int) substr($last, strlen($stem))) + 1 : 1;

        return $stem.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function tierPrice(?Product $product, ?string $tier): float
    {
        if (! $product) {
            return 0.0;
        }

        return $tier === 'wholesale' && $product->wholesale_price !== null
            ? (float) $product->wholesale_price
            : (float) $product->sale_price;
    }

    private function assertEditable(PreparationOrder $order): void
    {
        if (! $order->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'الطلب ده '.$order->statusLabel().' — مابقاش ينفع يتعدّل.',
            ]);
        }
    }

    /**
     * تجميع البنود المكررة.
     *
     * لو نفس الصنف اتكتب في أكتر من سطر، بنجمّعه في سطر واحد عشان فحص
     * المتاح يبقى على إجمالي الكمية — من غير كده سطرين كل واحد جوه
     * المتاح لكن مجموعهم أكبر منه.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'لازم تضيف صنف واحد على الأقل للطلب.',
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

            if (isset($lines[$product->id])) {
                $lines[$product->id]['quantity'] += $quantity;

                continue;
            }

            $lines[$product->id] = ['product' => $product, 'quantity' => $quantity];
        }

        return array_values($lines);
    }

    /**
     * رفض الطلب لو المتاح مش كافي.
     *
     * المتاح هنا بيخصم حجز الطلبات التانية — يعني تاجرين ماينفعش
     * يتحجزلهم نفس الكرتونة.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    private function assertAvailable(array $lines, int $warehouseId): void
    {
        $errors = [];

        foreach ($lines as $index => $line) {
            $available = $this->inventory->availableFor($line['product']->id, $warehouseId);

            if ($available + 1e-6 < $line['quantity']) {
                $errors["items.{$index}.quantity"] = sprintf(
                    'المتاح من «%s» %s %s بس، والمطلوب %s %s.',
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

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
    }
}
