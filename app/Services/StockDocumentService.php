<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * أذون الإضافة والخصم، وورقة الجرد.
 *
 * **الاعتماد هو اللي بيحرّك البضاعة، مش الحفظ.** الإذن بيتكتب مسودة
 * وبيتراجع، وأول ما يتعمد بتتعمل حركات المخزون. قبل كده الورقة مالهاش
 * أي أثر على أي رصيد — وده المقصود: اللي بيكتب الورقة في المخزن مش
 * بالضرورة اللي بيوافق عليها.
 *
 * التقييم بالتكلفة: كل بند بيتجمّد عليه `unit_cost` وقت الكتابة، فحركة
 * المخزون والتقييم بيقروا نفس الرقم مهما تغيّرت تكلفة الصنف بعد كده.
 */
class StockDocumentService
{
    public function __construct(private readonly InventoryService $inventory) {}

    // ── أذون الإضافة والخصم ───────────────────────────────────────

    /**
     * إذن جديد — مسودة، مالهاش أثر لحد الاعتماد.
     *
     * @param  array{type: string, warehouse_id: int, date: string, reason: string, notes?: ?string, items: list<array{product_id: int, quantity: float, unit_cost?: ?float}>}  $data
     */
    public function createAdjustment(array $data, User $user, ?StockCount $fromCount = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $user, $fromCount) {
            $type = $data['type'] === StockAdjustment::TYPE_DEDUCTION
                ? StockAdjustment::TYPE_DEDUCTION
                : StockAdjustment::TYPE_ADDITION;

            $lines = $this->buildAdjustmentLines($data['items']);

            $adjustment = StockAdjustment::create([
                'number' => $this->nextAdjustmentNumber($type),
                'type' => $type,
                'warehouse_id' => (int) $data['warehouse_id'],
                'user_id' => $user->id,
                'stock_count_id' => $fromCount?->id,
                'date' => $data['date'],
                'reason' => $data['reason'],
                'status' => StockAdjustment::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $adjustment->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                ]);
            }

            return $adjustment->fresh(['items.product', 'warehouse']);
        });
    }

    /**
     * اعتماد الإذن — ده اللي بيعمل حركات المخزون.
     *
     * إذن الخصم بيتفحص على **المتاح** مش الرصيد الخام: بضاعة محجوزة
     * بطلب تحضير لسه على الرف، بس مش ملكنا نخصمها.
     */
    public function approveAdjustment(StockAdjustment $adjustment, User $user): StockAdjustment
    {
        if ($adjustment->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'الإذن ده اتعمد قبل كده.',
            ]);
        }

        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment->load('items.product');

            if (! $adjustment->isAddition()) {
                $this->assertDeductionIsCovered($adjustment);
            }

            foreach ($adjustment->items as $item) {
                $quantity = $adjustment->isAddition()
                    ? (float) $item->quantity
                    : -(float) $item->quantity;

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'type' => StockMovement::TYPE_ADJUST,
                    'quantity' => $quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'user_id' => $user->id,
                    'date' => $adjustment->date->toDateString(),
                    'notes' => $adjustment->typeLabel().' '.$adjustment->number.' — '.$adjustment->reasonLabel(),
                ]);
            }

            $adjustment->update([
                'status' => StockAdjustment::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $adjustment->recordActivity('approved', [
                'number' => $adjustment->number,
                'type' => $adjustment->typeLabel(),
                'value' => $adjustment->value(),
                'by' => $user->name,
            ]);

            /*
             * فحص النواقص بعد ما المعاملة تخلص — إذن خصم ممكن ينزّل صنف
             * تحت حد الطلب، والتنبيه مايتبعتش على اعتماد اتلغى.
             */
            $productIds = $adjustment->items->pluck('product_id')->all();

            DB::afterCommit(fn () => app(StockAlertService::class)->check($productIds));

            return $adjustment->fresh(['items.product', 'approver']);
        });
    }

    // ── الجرد ─────────────────────────────────────────────────────

    /**
     * ورقة جرد جديدة — بتتجمّد عليها أرصدة النظام وقت الفتح.
     *
     * @param  array{warehouse_id: int, category_id?: ?int, date: string, notes?: ?string}  $data
     */
    public function createCount(array $data, User $user): StockCount
    {
        return DB::transaction(function () use ($data, $user) {
            $warehouseId = (int) $data['warehouse_id'];
            $categoryId = $data['category_id'] ?? null;

            $count = StockCount::create([
                'number' => $this->nextCountNumber(),
                'warehouse_id' => $warehouseId,
                'category_id' => $categoryId ?: null,
                'user_id' => $user->id,
                'date' => $data['date'],
                'status' => StockCount::STATUS_OPEN,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($this->countableProducts($warehouseId, $categoryId) as $row) {
                $count->items()->create([
                    'product_id' => $row['id'],
                    'system_quantity' => $row['quantity'],
                    'counted_quantity' => null,
                ]);
            }

            if ($count->items()->count() === 0) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'مفيش أصناف تتجرد في المخزن ده.',
                ]);
            }

            return $count->fresh(['items.product', 'warehouse', 'category']);
        });
    }

    /**
     * حفظ الكميات المعدودة — من غير اعتماد.
     *
     * بيتحفظ على مراحل: الجرد بياخد يوم، والمخزن بيكتب اللي عدّه أول
     * بأول من غير ما يقفل الورقة.
     *
     * @param  array<int, array{id: int, counted_quantity: float|null}>  $items
     */
    public function saveCounted(StockCount $count, array $items): StockCount
    {
        $this->assertCountIsOpen($count);

        return DB::transaction(function () use ($count, $items) {
            foreach ($items as $row) {
                $item = $count->items()->findOrFail($row['id']);
                $counted = $row['counted_quantity'];

                if ($counted !== null && (float) $counted < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'الكمية المعدودة ماينفعش تكون بالسالب.',
                    ]);
                }

                $item->update([
                    'counted_quantity' => $counted === null ? null : round((float) $counted, 3),
                ]);
            }

            return $count->fresh('items.product');
        });
    }

    /**
     * اعتماد الجرد — بيولّد أذون الإضافة والخصم للفروق ويعتمدها.
     *
     * البند اللي مااتعدّش بيتسيب على حاله: ورقة جرد نص مليانة معناها
     * المخزن لسه بيعد، مش إن الباقي كله صفر. لو عدّيناه كصفر، أول
     * اعتماد كان هيمسح نص المخزن.
     *
     * @return array{count: StockCount, addition: StockAdjustment|null, deduction: StockAdjustment|null}
     */
    public function approveCount(StockCount $count, User $user): array
    {
        $this->assertCountIsOpen($count);

        return DB::transaction(function () use ($count, $user) {
            $count->load('items.product');

            $additions = [];
            $deductions = [];

            foreach ($count->items as $item) {
                $difference = $item->difference();

                // مااتعدّش، أو اتعد وطلع مطابق — مفيش إذن
                if ($difference === null || abs($difference) < 1e-6) {
                    continue;
                }

                $line = [
                    'product_id' => $item->product_id,
                    'quantity' => abs($difference),
                    'unit_cost' => (float) ($item->product?->purchase_price ?? 0),
                ];

                if ($difference > 0) {
                    $additions[] = $line;
                } else {
                    $deductions[] = $line;
                }
            }

            $shared = [
                'warehouse_id' => $count->warehouse_id,
                'date' => $count->date->toDateString(),
                'reason' => 'count',
                'notes' => 'فروق جرد '.$count->number,
            ];

            $addition = $additions === [] ? null : $this->approveAdjustment(
                $this->createAdjustment(
                    [...$shared, 'type' => StockAdjustment::TYPE_ADDITION, 'items' => $additions],
                    $user,
                    $count,
                ),
                $user,
            );

            $deduction = $deductions === [] ? null : $this->approveAdjustment(
                $this->createAdjustment(
                    [...$shared, 'type' => StockAdjustment::TYPE_DEDUCTION, 'items' => $deductions],
                    $user,
                    $count,
                ),
                $user,
            );

            $count->update([
                'status' => StockCount::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $count->recordActivity('approved', array_filter([
                'number' => $count->number,
                'by' => $user->name,
                'addition' => $addition?->number,
                'deduction' => $deduction?->number,
            ]));

            return [
                'count' => $count->fresh(['items.product', 'adjustments']),
                'addition' => $addition,
                'deduction' => $deduction,
            ];
        });
    }

    public function cancelCount(StockCount $count, User $user): StockCount
    {
        $this->assertCountIsOpen($count);

        $count->update(['status' => StockCount::STATUS_CANCELLED]);
        $count->recordActivity('cancelled', ['number' => $count->number, 'by' => $user->name]);

        return $count->fresh();
    }

    // ── مساعدات ───────────────────────────────────────────────────

    /**
     * الأصناف اللي بتتجرد في مخزن — ومعاها رصيد النظام.
     *
     * بتشمل الصنف اللي رصيده صفر: الجرد بيكشف البضاعة اللي موجودة على
     * الرف والنظام مش عارف عنها حاجة، وده أهم اكتشاف في الجرد أصلاً.
     *
     * @return Collection<int, array{id: int, quantity: float}>
     */
    public function countableProducts(int $warehouseId, ?int $categoryId = null): Collection
    {
        $balances = DB::table('stock_movements')
            ->where('warehouse_id', $warehouseId)
            ->groupBy('product_id')
            ->selectRaw('product_id, sum(quantity) as qty')
            ->pluck('qty', 'product_id');

        return Product::query()
            ->where('is_active', true)
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get(['id'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'quantity' => round((float) ($balances[$product->id] ?? 0), 3),
            ]);
    }

    /** ترقيم تسلسلي — ADD-2026-00001 / DED-2026-00001 */
    public function nextAdjustmentNumber(string $type): string
    {
        $prefix = $type === StockAdjustment::TYPE_DEDUCTION ? 'DED' : 'ADD';

        return $this->nextNumber(StockAdjustment::query(), $prefix);
    }

    /** ترقيم ورقة الجرد — CNT-2026-00001 */
    public function nextCountNumber(): string
    {
        return $this->nextNumber(StockCount::query(), 'CNT');
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private function nextNumber($query, string $prefix): string
    {
        $stem = $prefix.'-'.Carbon::today()->year.'-';

        // lockForUpdate عشان مستندين في نفس اللحظة ما ياخدوش نفس الرقم
        $last = $query->where('number', 'like', $stem.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last ? ((int) substr($last, strlen($stem))) + 1 : 1;

        return $stem.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function buildAdjustmentLines(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'لازم تضيف صنف واحد على الأقل للإذن.',
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

            // التكلفة الحالية هي الافتراضي، والمستخدم يقدر يعدّلها
            $cost = isset($item['unit_cost']) && $item['unit_cost'] !== null && $item['unit_cost'] !== ''
                ? round((float) $item['unit_cost'], 2)
                : (float) $product->purchase_price;

            if (isset($lines[$product->id])) {
                $lines[$product->id]['quantity'] += $quantity;

                continue;
            }

            $lines[$product->id] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_cost' => $cost,
            ];
        }

        return array_values($lines);
    }

    /**
     * إذن الخصم ماينفعش ينزّل المتاح تحت الصفر.
     *
     * الفحص على المتاح مش الرصيد: بضاعة محجوزة بطلب تحضير لسه على
     * الرف، بس مش ملكنا نخصمها — وإلا الطلب بيروح يتجهّز ويلاقي الرف
     * فاضي.
     */
    private function assertDeductionIsCovered(StockAdjustment $adjustment): void
    {
        $errors = [];

        foreach ($adjustment->items as $index => $item) {
            $available = $this->inventory->availableFor($item->product_id, $adjustment->warehouse_id);

            if ($available + 1e-6 < (float) $item->quantity) {
                $errors["items.{$index}.quantity"] = sprintf(
                    'المتاح من «%s» %s %s بس، والإذن بيخصم %s.',
                    $item->product?->name,
                    $this->formatQuantity($available),
                    $item->product?->unit,
                    $this->formatQuantity((float) $item->quantity),
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertCountIsOpen(StockCount $count): void
    {
        if (! $count->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'ورقة الجرد دي '.$count->statusLabel().' — مابقتش تتعدّل.',
            ]);
        }
    }

    private function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
    }
}
