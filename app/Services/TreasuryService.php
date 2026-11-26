<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * الخزينة: الداخل (تحصيلات من التجار) والخارج (مدفوعات للموردين).
 *
 * المدفوعات ممكن تكون بالدولار أو اليورو أو الليرة، فكل الأرقام المجمّعة
 * بتتحوّل للجنيه بـ amount × exchange_rate. العرض بيوضّح المبلغ الأصلي
 * وما يقابله بالجنيه في عمودين منفصلين عشان ما نخلطش عملات ببعض.
 */
class TreasuryService
{
    public function __construct(private readonly AttachmentService $attachments) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function ledger(array $filters = []): array
    {
        $payments = Payment::query()
            ->with([
                'customer:id,name', 'supplier:id,name', 'invoice:id,number,type',
                'expense:id,number,expense_category_id', 'expense.category:id,name',
                'attachments.user:id,name',
            ])
            ->when(
                filled($filters['type'] ?? null),
                fn (Builder $query) => $query->where('type', $filters['type'])
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
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $rows = $payments->map(function (Payment $payment) {
            $egp = round((float) $payment->amount * (float) $payment->exchange_rate, 2);

            return [
                'id' => $payment->id,
                'date' => $payment->date->toDateString(),
                'type' => $payment->type,
                // المصروف مالوش طرف — بنعرض تصنيفه مكانه
                'party' => $payment->customer?->name
                    ?? $payment->supplier?->name
                    ?? $payment->expense?->category?->name,
                'partyLink' => $payment->customer_id
                    ? "/customers/{$payment->customer_id}"
                    : ($payment->supplier_id ? "/suppliers/{$payment->supplier_id}" : null),
                'method' => $payment->method,
                'methodLabel' => PaymentService::METHOD_LABELS[$payment->method] ?? $payment->method,
                'reference' => $payment->reference,
                'invoice' => $payment->invoice?->number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'exchangeRate' => (float) $payment->exchange_rate,
                'amountEgp' => $egp,
                'signedEgp' => $payment->type === Payment::TYPE_RECEIPT ? $egp : -$egp,
                'attachments' => $payment->attachments->map(
                    fn (Attachment $attachment) => $this->attachments->present($attachment)
                )->all(),
            ];
        });

        return [
            'rows' => $rows,
            'totals' => $this->totals($filters),
        ];
    }

    /**
     * إجماليات الفترة بالجنيه — بتتحسب على كل السندات مش الـ 300 المعروضين.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function totals(array $filters = []): array
    {
        $base = fn () => Payment::query()
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
            );

        $in = (float) $base()->where('type', Payment::TYPE_RECEIPT)
            ->sum(DB::raw('amount * exchange_rate'));

        /*
         * الخارج = مدفوعات الموردين + المصروفات.
         *
         * المصروف نوع لوحده عشان عمولة المندوب ما تتأثرش بيه، لكنه فلوس
         * خارجة زيه زي أي دفعة — فلازم يدخل في صافي الخزينة.
         */
        $out = (float) $base()
            ->whereIn('type', [Payment::TYPE_DISBURSEMENT, Payment::TYPE_EXPENSE])
            ->sum(DB::raw('amount * exchange_rate'));

        return [
            'in' => round($in, 2),
            'out' => round($out, 2),
            'net' => round($in - $out, 2),
        ];
    }

    /**
     * تجميع حسب طريقة الدفع — نقدي / بنكي / شيكات.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byMethod(array $filters = []): Collection
    {
        return Payment::query()
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $query) => $query->where('date', '>=', Carbon::parse($filters['from'])->toDateString())
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $query) => $query->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString())
            )
            ->selectRaw('method, type, count(*) as count, sum(amount * exchange_rate) as egp')
            ->groupBy('method', 'type')
            ->get()
            ->groupBy('method')
            ->map(fn ($group, $method) => [
                'method' => $method,
                'label' => PaymentService::METHOD_LABELS[$method] ?? $method,
                'in' => round((float) ($group->firstWhere('type', Payment::TYPE_RECEIPT)->egp ?? 0), 2),
                'out' => round((float) ($group->firstWhere('type', Payment::TYPE_DISBURSEMENT)->egp ?? 0), 2),
                'count' => (int) $group->sum('count'),
            ])
            ->values();
    }
}
