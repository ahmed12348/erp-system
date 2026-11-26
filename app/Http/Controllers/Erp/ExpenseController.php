<?php

namespace App\Http\Controllers\Erp;

use App\Enums\ErpAbility;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AttachmentService;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * المصروفات وشجرة تصنيفاتها.
 *
 * التسجيل للمدير والمحاسب (`ManageMasterData`) — المصروف بيطلّع فلوس من
 * الخزينة، فمش عمل مندوب. تعديل الشجرة للمدير لوحده.
 */
class ExpenseController extends Controller
{
    /** طرق الدفع المتاحة للمصروف — نقدي أو بنكي، مفيش شيكات */
    private const METHODS = ['cash', 'bank'];

    public function __construct(
        private readonly ExpenseService $expenses,
        private readonly AttachmentService $attachments,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $filters = $request->only(['search', 'category_id', 'method', 'from', 'to']);

        return Inertia::render('expenses/Index', [
            'expenses' => $this->expenses->paginate($filters)
                ->through(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'number' => $expense->number,
                    'date' => $expense->date->toDateString(),
                    'category' => $expense->category?->name,
                    'group' => $expense->category?->parent?->name,
                    'amount' => (float) $expense->amount,
                    'method' => PaymentService::METHOD_LABELS[$expense->method] ?? $expense->method,
                    'paidTo' => $expense->paid_to,
                    'createdBy' => $expense->user?->name,
                ]),
            'total' => $this->expenses->totalBetween(
                $filters['from'] ?? null,
                $filters['to'] ?? null,
            ),
            'hasAny' => Expense::query()->exists(),
            'filters' => $filters,
            'tree' => $this->treeOptions(),
            'categories' => $this->expenses->selectableCategories(),
            'methods' => $this->methodOptions(),
            'today' => now()->toDateString(),
            'breadcrumbs' => $this->breadcrumbs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageMasterData->value);

        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(self::METHODS)],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => [
                'nullable', 'file',
                'mimes:'.implode(',', AttachmentService::ALLOWED_MIMES),
                'max:'.AttachmentService::MAX_SIZE_KB,
            ],
        ], [
            'expense_category_id.required' => 'اختار بند المصروف.',
            'amount.gt' => 'المبلغ لازم يكون أكبر من صفر.',
            'date.before_or_equal' => 'ماينفعش مصروف بتاريخ في المستقبل.',
        ]);

        $expense = $this->expenses->create($data, $request->user());

        // إيصال المصروف — صورة أو PDF
        if ($request->hasFile('attachment')) {
            $this->attachments->store($expense, $request->file('attachment'), $request->user());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'تم تسجيل المصروف '.$expense->number.' وخصمه من الخزينة.',
        ]);

        return back();
    }

    // ── شجرة التصنيفات ────────────────────────────────────────────

    public function categories(): Response
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        return Inertia::render('expenses/Categories', [
            'tree' => ExpenseCategory::tree()->map(fn (ExpenseCategory $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'isActive' => $group->is_active,
                'expenseCount' => $group->expenses_count,
                'children' => $group->children->map(fn (ExpenseCategory $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'code' => $child->code,
                    'isActive' => $child->is_active,
                    'expenseCount' => $child->expenses_count,
                ])->all(),
            ])->all(),
            'breadcrumbs' => [
                ...$this->breadcrumbs(),
                ['title' => 'تصنيفات المصروفات', 'href' => route('expense-categories.index')],
            ],
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:expense_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', 'unique:expense_categories,code'],
        ], [
            'name.required' => 'اسم التصنيف مطلوب.',
            'code.unique' => 'الكود ده مستعمل في تصنيف تاني.',
        ]);

        ExpenseCategory::create([
            ...$data,
            'sort_order' => (int) ExpenseCategory::query()
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order') + 10,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم إضافة التصنيف.']);

        return back();
    }

    public function updateCategory(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:20',
                Rule::unique('expense_categories', 'code')->ignore($expenseCategory->id),
            ],
            'is_active' => ['boolean'],
        ]);

        $expenseCategory->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حفظ التصنيف.']);

        return back();
    }

    public function destroyCategory(ExpenseCategory $expenseCategory): RedirectResponse
    {
        Gate::authorize(ErpAbility::ManageUsers->value);

        /*
         * التصنيف اللي عليه مصروفات مابيتمسحش — بيتوقف.
         *
         * الحذف كان هيترك مصروفات من غير تصنيف، والتقرير بالتصنيف
         * بيفقد سطور من غير ما حد ياخد باله.
         */
        if ($expenseCategory->expenses()->exists() || $expenseCategory->children()->exists()) {
            return back()->withErrors([
                'category' => 'التصنيف ده عليه مصروفات أو بنود — أوقفه بدل ما تمسحه.',
            ]);
        }

        $expenseCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'تم حذف التصنيف.']);

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function treeOptions(): array
    {
        return ExpenseCategory::tree()->map(fn (ExpenseCategory $group) => [
            'id' => $group->id,
            'name' => $group->name,
            'children' => $group->children->map(fn (ExpenseCategory $child) => [
                'id' => $child->id,
                'name' => $child->name,
            ])->all(),
        ])->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function methodOptions(): array
    {
        return array_map(
            fn (string $value): array => [
                'value' => $value,
                'label' => PaymentService::METHOD_LABELS[$value] ?? $value,
            ],
            self::METHODS,
        );
    }

    /**
     * @return array<int, array{title: string, href: string}>
     */
    private function breadcrumbs(): array
    {
        return [
            ['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')],
            ['title' => 'المصروفات', 'href' => route('expenses.index')],
        ];
    }
}
