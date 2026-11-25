<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Concerns\PreventsDeletingUsedRecords;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * بيانات التجار: القائمة، ملخّص الحساب، وكشف الحساب بالرصيد الجاري.
 */
class CustomerService
{
    use PreventsDeletingUsedRecords;

    /**
     * قائمة التجار مع الرصيد — بحث بالاسم أو التليفون وفلتر بالمحافظة.
     *
     * @param  array{search?: string|null, city?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->select([
                'id', 'name', 'phone', 'city', 'address', 'price_tier', 'sales_rep_id',
                'credit_limit', 'opening_balance', 'notes', 'is_active',
            ])
            ->with('salesRep:id,name')
            ->selectRaw($this->balanceSql().' as balance')
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('name', 'like', $term)->orWhere('phone', 'like', $term);
                })
            )
            ->when(
                filled($filters['city'] ?? null),
                fn (Builder $query) => $query->where('city', $filters['city'])
            )
            // التجار اللي عدّوا حد المديونية بس
            ->when(
                ($filters['status'] ?? null) === 'over_limit',
                fn (Builder $query) => $query
                    ->whereNotNull('credit_limit')
                    ->whereRaw($this->balanceSql().' > customers.credit_limit')
            )
            ->when(
                ($filters['status'] ?? null) === 'indebted',
                fn (Builder $query) => $query->whereRaw($this->balanceSql().' > 0')
            )
            ->orderByDesc('balance')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** المحافظات الموجودة فعلاً عند التجار — لقائمة الفلتر */
    public function governorates(): Collection
    {
        return Customer::query()
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    /**
     * ملخّص حساب التاجر.
     *
     * @return array<string, mixed>
     */
    public function summary(Customer $customer): array
    {
        $confirmed = $customer->invoices()->where('status', Invoice::STATUS_CONFIRMED);

        $sales = (float) (clone $confirmed)->where('type', Invoice::TYPE_SALE)->sum('total');
        $returns = (float) (clone $confirmed)->where('type', Invoice::TYPE_SALE_RETURN)->sum('total');
        $paid = (float) $customer->payments()->sum('amount');
        $balance = (float) $customer->opening_balance + $sales - $returns - $paid;
        $limit = $customer->credit_limit ? (float) $customer->credit_limit : null;

        return [
            'sales' => $sales,
            'returns' => $returns,
            'paid' => $paid,
            'balance' => $balance,
            'invoiceCount' => (clone $confirmed)->where('type', Invoice::TYPE_SALE)->count(),
            'creditLimit' => $limit,
            'creditUsage' => $limit && $limit > 0 ? round(($balance / $limit) * 100, 1) : null,
            'overLimit' => $limit !== null && $balance > $limit,
        ];
    }

    /**
     * كشف حساب بالرصيد الجاري.
     *
     * الرصيد الافتتاحي = الرصيد الافتتاحي للتاجر + كل الحركة قبل تاريخ البداية،
     * عشان الكشف يبقى صح حتى لو المستخدم فلتر على فترة في النص.
     *
     * `$search` بيدوّر برقم الفاتورة (أو مرجع السند) — «التاجر ده بيقول
     * إنه دفع فاتورة S-2026-00042، فين؟» سؤال بيتسأل كل يوم، وقبل كده
     * كان بيتجاوب بالتدوير بالعين في كشف من مية سطر.
     *
     * @return array{opening: float, rows: list<array<string, mixed>>, closing: float, totals: array<string, float>}
     */
    public function statement(Customer $customer, ?string $from = null, ?string $to = null, ?string $search = null): array
    {
        $opening = (float) $customer->opening_balance + $this->movementBefore($customer, $from);

        $rows = $this->ledgerRows($customer, $from, $to)
            ->sortBy([['date', 'asc'], ['id', 'asc']])
            ->values();

        $balance = $opening;
        $debit = 0.0;
        $credit = 0.0;
        $output = [];

        foreach ($rows as $row) {
            $balance += $row['debit'] - $row['credit'];
            $debit += $row['debit'];
            $credit += $row['credit'];

            $output[] = [...$row, 'balance' => round($balance, 2)];
        }

        return [
            'opening' => round($opening, 2),
            /*
             * البحث بيتعمل بعد حساب الرصيد الجاري مش قبله: السطر اللي
             * بيظهر بيفضل شايل رصيده الحقيقي من الكشف الكامل. ولو
             * اتفلتر الأول، كل رصيد في العمود كان هيبقى رقم مالوش
             * معنى — والافتتاحي والختامي والإجماليات بتفضل على الفترة
             * كلها عشان الكشف يفضل مقفول.
             */
            'rows' => self::matching($output, $search),
            'closing' => round($balance, 2),
            'totals' => ['debit' => round($debit, 2), 'credit' => round($credit, 2)],
        ];
    }

    /**
     * سطور الكشف اللي مرجعها فيه النص المدوّر عليه.
     *
     * مشترك بين كشف التاجر وكشف المورد — نفس البحث بالظبط، فما ينفعش
     * يتكتب مرتين ويفرقوا مع أول تعديل.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function matching(array $rows, ?string $search): array
    {
        if (blank($search)) {
            return $rows;
        }

        $term = mb_strtolower(trim($search));

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => str_contains(
                mb_strtolower((string) ($row['reference'] ?? '')),
                $term,
            ),
        ));
    }

    /**
     * سطور الكشف: الفواتير والمرتجعات والتحصيلات.
     * مدين (debit) = بيزوّد مديونية التاجر، دائن (credit) = بيقلّلها.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function ledgerRows(Customer $customer, ?string $from, ?string $to): Collection
    {
        $invoices = $customer->invoices()
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->whereIn('type', [Invoice::TYPE_SALE, Invoice::TYPE_SALE_RETURN])
            ->tap(fn ($query) => $this->applyDateRange($query, 'date', $from, $to))
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'kind' => $invoice->type,
                'date' => $invoice->date->toDateString(),
                'reference' => $invoice->number,
                'description' => $invoice->type === Invoice::TYPE_SALE
                    ? 'فاتورة بيع'
                    : 'مرتجع مبيعات',
                'debit' => $invoice->type === Invoice::TYPE_SALE ? (float) $invoice->total : 0.0,
                'credit' => $invoice->type === Invoice::TYPE_SALE_RETURN ? (float) $invoice->total : 0.0,
                'link' => $invoice->type === Invoice::TYPE_SALE ? "/sales/{$invoice->id}" : null,
            ]);

        $payments = $customer->payments()
            ->tap(fn ($query) => $this->applyDateRange($query, 'date', $from, $to))
            ->get()
            ->map(fn ($payment) => [
                'id' => 1_000_000 + $payment->id, // عشان الترتيب الثانوي ما يتضاربش مع الفواتير
                'kind' => 'payment',
                'date' => $payment->date->toDateString(),
                'reference' => $payment->reference ?? '—',
                'description' => 'تحصيل — '.$this->methodLabel($payment->method),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'link' => null,
            ]);

        return $invoices->concat($payments);
    }

    /**
     * صافي الحركة قبل تاريخ البداية — ده اللي بيتحوّل لرصيد افتتاحي للكشف.
     *
     * لازم تكون "أقل من" تاريخ البداية بالظبط، مش "أقل من أو يساوي":
     * لو حسبناها شاملة اليوم ده، حركة اليوم الأول هتتعدّ مرتين — مرة في
     * الرصيد الافتتاحي ومرة في سطور الكشف. ولو مفيش تاريخ بداية أصلاً
     * فمفيش حاجة "قبل الفترة" والرصيد الافتتاحي هو رصيد التاجر الافتتاحي بس.
     */
    private function movementBefore(Customer $customer, ?string $from): float
    {
        if (blank($from)) {
            return 0.0;
        }

        $boundary = Carbon::parse($from)->toDateString();

        $confirmed = fn () => $customer->invoices()
            ->where('status', Invoice::STATUS_CONFIRMED)
            ->where('date', '<', $boundary);

        $sales = (float) $confirmed()->where('type', Invoice::TYPE_SALE)->sum('total');
        $returns = (float) $confirmed()->where('type', Invoice::TYPE_SALE_RETURN)->sum('total');

        $paid = (float) $customer->payments()->where('date', '<', $boundary)->sum('amount');

        return $sales - $returns - $paid;
    }

    /**
     * فلتر تاريخ شامل لليوم الأخير وشغّال على MySQL و SQLite.
     * (الـ cast بتاع date بيكتب Y-m-d H:i:s، فالحد الأعلى لازم يبقى "أقل من بكرة")
     */
    private function applyDateRange($query, string $column, ?string $from, ?string $to): void
    {
        if (filled($from)) {
            $query->where($column, '>=', Carbon::parse($from)->toDateString());
        }

        if (filled($to)) {
            $query->where($column, '<', Carbon::parse($to)->addDay()->toDateString());
        }
    }

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'bank' => 'تحويل بنكي',
            'check' => 'شيك',
            default => 'نقدي',
        };
    }

    private function balanceSql(): string
    {
        $sale = Invoice::TYPE_SALE;
        $saleReturn = Invoice::TYPE_SALE_RETURN;
        $confirmed = Invoice::STATUS_CONFIRMED;

        return "(customers.opening_balance
            + (select coalesce(sum(total), 0) from invoices where invoices.customer_id = customers.id and invoices.type = '{$sale}' and invoices.status = '{$confirmed}')
            - (select coalesce(sum(total), 0) from invoices where invoices.customer_id = customers.id and invoices.type = '{$saleReturn}' and invoices.status = '{$confirmed}')
            - (select coalesce(sum(amount), 0) from payments where payments.customer_id = customers.id))";
    }

    /** إجمالي المديونيات لكل التجار — بيستخدمه الداشبورد والتقارير */
    public function totalReceivables(): float
    {
        return (float) Customer::query()
            ->selectRaw('coalesce(sum('.$this->balanceSql().'), 0) as total')
            ->value('total');
    }

    /** @return Builder<Customer> */
    public function withBalance(): Builder
    {
        return Customer::query()->selectRaw('customers.*, '.$this->balanceSql().' as balance');
    }

    public function balanceFor(Customer $customer): float
    {
        return (float) DB::table('customers')
            ->where('id', $customer->id)
            ->selectRaw($this->balanceSql().' as balance')
            ->value('balance');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer
    {
        return DB::transaction(fn () => Customer::create($this->attributes($data)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update($this->attributes($data));

            return $customer;
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(Customer $customer): void
    {
        $this->guardAgainstDeletion('customer', $customer->name, [
            'فاتورة' => $customer->invoices()->count(),
            'سند تحصيل' => $customer->payments()->count(),
        ]);

        DB::transaction(fn () => $customer->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'city' => $data['city'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'price_tier' => $data['price_tier'] ?? 'sale',
            // المفتاح نفسه بيبقى ناقص لما الحقل اختياري ومابيتبعتش
            'sales_rep_id' => ($data['sales_rep_id'] ?? null) ?: null,
            // الفاضي معناه «بدون حد مديونية»، مش صفر
            'credit_limit' => ($data['credit_limit'] ?? null) === '' ? null : ($data['credit_limit'] ?? null),
            'opening_balance' => $data['opening_balance'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }
}
