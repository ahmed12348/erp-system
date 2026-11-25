<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * قوائم فواتير الشراء والفلاتر بتاعتها.
 *
 * مقابل `SalesQueryService` بالظبط. الاستعلام كان متكتب جوه الكنترولر،
 * وده كان معناه إن التحميل لازم يكرّره — ونسختين من نفس الفلاتر بيفرقوا
 * عن بعض مع أول تعديل، فالملف المحمّل يطلع بصفوف غير اللي على الشاشة.
 */
class PurchaseQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * كل فواتير الشراء اللي الفلاتر دي بتجيبها — من غير ترقيم صفحات.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Invoice>
     */
    public function query(array $filters): Builder
    {
        return Invoice::query()
            ->where('type', Invoice::TYPE_PURCHASE)
            ->with(['supplier:id,name,country', 'warehouse:id,name'])
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $query->where(function (Builder $inner) use ($filters) {
                    $term = '%'.$filters['search'].'%';
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', $term));
                })
            )
            ->when(
                filled($filters['supplier_id'] ?? null),
                fn (Builder $query) => $query->where('supplier_id', $filters['supplier_id'])
            )
            ->when(
                filled($filters['currency'] ?? null),
                fn (Builder $query) => $query->where('currency', $filters['currency'])
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $query) => $query->where('date', '>=', Carbon::parse($filters['from'])->toDateString())
            )
            ->when(
                filled($filters['to'] ?? null),
                // "أقل من بكرة" عشان اليوم الأخير يدخل على MySQL و SQLite
                fn (Builder $query) => $query->where('date', '<', Carbon::parse($filters['to'])->addDay()->toDateString())
            )
            ->latest('date')
            ->latest('id');
    }

    /**
     * بيانات الصف زي ما الشاشة بتعرضها.
     *
     * الشاشة والتحميل بياخدوا من هنا الاتنين، فالأعمدة واحدة.
     *
     * @return array<string, mixed>
     */
    public function row(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status,
            'date' => $invoice->date->toDateString(),
            'supplier' => $invoice->supplier?->name,
            'country' => $invoice->supplier?->country,
            'warehouse' => $invoice->warehouse?->name,
            'currency' => $invoice->currency,
            'exchangeRate' => (float) $invoice->exchange_rate,
            'total' => (float) $invoice->total,
            'totalEgp' => round((float) $invoice->total * (float) $invoice->exchange_rate, 2),
            'landed' => (float) $invoice->shipping_cost + (float) $invoice->customs_cost + (float) $invoice->clearance_cost,
            'remaining' => $invoice->remaining,
        ];
    }
}
