<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * المصروفات.
 *
 * المصروف بيطلع من الخزينة: كل مصروف بيعمل سند صرف في `payments` بنوع
 * `expense` جوه نفس المعاملة. الخزينة بتفضل مصدر واحد للفلوس بدل
 * جدولين بيتجمعوا على الشاشة — وده اللي بيخلّي «صافي الخزينة» رقم
 * واحد مش حسبة.
 */
class ExpenseService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Expense>
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Expense>
     */
    public function query(array $filters): Builder
    {
        return Expense::query()
            ->with(['category.parent', 'user:id,name'])
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('number', 'like', $term)
                        ->orWhere('paid_to', 'like', $term);
                })
            )
            ->when(
                filled($filters['category_id'] ?? null),
                // اختيار مجموعة بيجيب بنودها كلها — ده المتوقّع من شجرة
                fn (Builder $query) => $query->whereIn(
                    'expense_category_id',
                    $this->categoryWithChildren((int) $filters['category_id']),
                )
            )
            ->when(
                filled($filters['method'] ?? null),
                fn (Builder $query) => $query->where('method', $filters['method'])
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
     * تسجيل مصروف — ومعاه سند الصرف.
     *
     * @param  array{expense_category_id: int, date: string, amount: float, method: string, paid_to?: ?string, notes?: ?string}  $data
     */
    public function create(array $data, User $user): Expense
    {
        return DB::transaction(function () use ($data, $user) {
            $category = ExpenseCategory::findOrFail($data['expense_category_id']);

            /*
             * المصروف بيتسجّل على بند مش على مجموعة.
             *
             * «مصروفات ثابتة» مجموعة مالهاش معنى كبند صرف — ولو سمحنا
             * بيها، التقرير بالتصنيف بيطلع فيه سطر مجموعة جنب بنودها
             * ومحدش عارف يجمع.
             */
            if ($category->isGroup()) {
                throw ValidationException::withMessages([
                    'expense_category_id' => 'اختار بند مصروف مش مجموعة — «'.$category->name.'» مجموعة.',
                ]);
            }

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ لازم يكون أكبر من صفر.',
                ]);
            }

            $expense = Expense::create([
                'number' => $this->nextNumber(),
                'expense_category_id' => $category->id,
                'user_id' => $user->id,
                'date' => $data['date'],
                'amount' => $amount,
                'method' => $data['method'],
                'paid_to' => $data['paid_to'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // سند الصرف — ده اللي بيطلّع الفلوس من الخزينة
            $payment = Payment::create([
                'type' => Payment::TYPE_EXPENSE,
                'expense_id' => $expense->id,
                'amount' => $amount,
                'currency' => 'EGP',
                'exchange_rate' => 1,
                'method' => $data['method'],
                'date' => $data['date'],
                'reference' => $expense->number,
                'user_id' => $user->id,
                'notes' => $category->fullName().($data['paid_to'] ?? null ? ' — '.$data['paid_to'] : ''),
            ]);

            $expense->update(['payment_id' => $payment->id]);

            return $expense->fresh(['category.parent', 'payment']);
        });
    }

    /**
     * إجمالي المصروفات في فترة.
     *
     * بيدخل في «صافي الربح» على اللوحة وفي أدوات المساعد.
     */
    public function totalBetween(?string $from = null, ?string $to = null): float
    {
        return round((float) Expense::query()
            ->when($from, fn (Builder $query) => $query->where('date', '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn (Builder $query) => $query->where('date', '<', Carbon::parse($to)->addDay()->toDateString()))
            ->sum('amount'), 2);
    }

    /**
     * تقرير المصروفات بالتصنيف — مجمّع بالمجموعة ومفصّل بالبند.
     *
     * @return array{rows: list<array<string, mixed>>, total: float}
     */
    public function byCategory(?string $from = null, ?string $to = null): array
    {
        $totals = Expense::query()
            ->when($from, fn (Builder $query) => $query->where('date', '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn (Builder $query) => $query->where('date', '<', Carbon::parse($to)->addDay()->toDateString()))
            ->groupBy('expense_category_id')
            ->selectRaw('expense_category_id, sum(amount) as total, count(*) as entries')
            ->get()
            ->keyBy('expense_category_id');

        $rows = [];
        $grand = 0.0;

        foreach (ExpenseCategory::tree() as $group) {
            $groupTotal = 0.0;
            $children = [];

            foreach ($group->children as $child) {
                $row = $totals->get($child->id);
                $amount = round((float) ($row->total ?? 0), 2);
                $groupTotal += $amount;

                $children[] = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'code' => $child->code,
                    'entries' => (int) ($row->entries ?? 0),
                    'total' => $amount,
                ];
            }

            // المجموعة نفسها ممكن يكون عليها مصروفات من بيانات قديمة
            $own = $totals->get($group->id);
            $groupTotal += round((float) ($own->total ?? 0), 2);
            $grand += $groupTotal;

            $rows[] = [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'total' => round($groupTotal, 2),
                'children' => $children,
            ];
        }

        return ['rows' => $rows, 'total' => round($grand, 2)];
    }

    /** ترقيم تسلسلي — EXP-2026-00001 */
    public function nextNumber(): string
    {
        $stem = 'EXP-'.Carbon::today()->year.'-';

        // lockForUpdate عشان مصروفين في نفس اللحظة ما ياخدوش نفس الرقم
        $last = Expense::query()
            ->where('number', 'like', $stem.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $sequence = $last ? ((int) substr($last, strlen($stem))) + 1 : 1;

        return $stem.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * البنود المتاحة للاختيار — بنود بس، من غير المجموعات.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function selectableCategories(): Collection
    {
        return ExpenseCategory::query()
            ->active()
            ->whereNotNull('parent_id')
            ->with('parent:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'group' => $category->parent?->name,
                'code' => $category->code,
            ]);
    }

    /**
     * التصنيف وأولاده — عشان الفلتر على مجموعة يجيب بنودها.
     *
     * @return array<int, int>
     */
    private function categoryWithChildren(int $categoryId): array
    {
        return [
            $categoryId,
            ...ExpenseCategory::query()->where('parent_id', $categoryId)->pluck('id')->all(),
        ];
    }
}
