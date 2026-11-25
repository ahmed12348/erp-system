<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * بيحوّل بيانات الشاشات لجدول جاهز للتصدير.
 *
 * مكان واحد لتعريف شكل كل تقرير، فالـ PDF والإكسيل بيطلعوا بنفس
 * الأعمدة ونفس الترتيب — مش نسختين بتفرقوا مع أول تعديل.
 *
 * الأرقام بترجع مرتين: نص منسّق للـ PDF، ورقم خام للإكسيل عشان
 * الجداول تجمعه.
 */
class ExportBuilder
{
    /**
     * أسماء التقارير العربية.
     *
     * دي كمان قايمة التقارير المتاحة — `ReportController` و
     * `ExportController` بيقروا منها، فتقرير جديد بيتزوّد هنا مرة واحدة
     * بدل ما يتكتب في تلات أماكن ويتنسي في واحد.
     */
    public const REPORT_TITLES = [
        'period' => 'تقرير المبيعات بالفترة',
        'sale_invoices' => 'تقرير المبيعات بالفاتورة',
        'purchase_invoices' => 'تقرير المشتريات بالفاتورة',
        'product' => 'تقرير المبيعات بالصنف',
        'customer' => 'تقرير المبيعات بالتاجر',
        'inventory' => 'تقييم المخزون',
    ];

    /** التقارير اللي مالهاش فترة — لقطة لحظية */
    public const SNAPSHOT_REPORTS = ['inventory'];

    /** حالات الفاتورة بالعربي — زي ما الشاشة بتعرضها */
    private const STATUSES = [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكّدة',
        'cancelled' => 'ملغية',
    ];

    /** أنواع الفواتير بالعربي */
    private const INVOICE_TYPES = [
        'sale' => 'بيع',
        'sale_return' => 'مرتجع بيع',
        'purchase' => 'شراء',
        'purchase_return' => 'مرتجع شراء',
    ];

    /**
     * جدول تقرير.
     *
     * @param  array<string, mixed>  $data
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}
     */
    public function report(string $report, array $data, bool $raw = false): array
    {
        return match ($report) {
            'product' => $this->byProduct($data, $raw),
            'customer' => $this->byCustomer($data, $raw),
            'inventory' => $this->inventory($data, $raw),
            'sale_invoices' => $this->byInvoice($data, $raw, 'التاجر'),
            'purchase_invoices' => $this->byInvoice($data, $raw, 'المورد'),
            default => $this->byPeriod($data, $raw),
        };
    }

    /**
     * كشف حساب تاجر أو مورد.
     *
     * @param  array<string, mixed>  $statement
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}
     */
    public function statement(array $statement, bool $raw = false): array
    {
        $rows = [];

        foreach ($statement['rows'] ?? [] as $row) {
            $rows[] = [
                $this->date($row['date'] ?? null),
                $row['label'] ?? ($row['description'] ?? '—'),
                // رقم الفاتورة للفواتير، ومرجع السند للتحصيلات
                $row['reference'] ?? '—',
                $this->amount($row['debit'] ?? 0, $raw),
                $this->amount($row['credit'] ?? 0, $raw),
                $this->amount($row['balance'] ?? 0, $raw),
            ];
        }

        return [
            'headers' => ['التاريخ', 'البيان', 'رقم الفاتورة', 'مدين', 'دائن', 'الرصيد'],
            'rows' => $rows,
            'numeric' => [3, 4, 5],
            'summary' => array_filter([
                'رصيد أول المدة' => isset($statement['opening'])
                    ? $this->money($statement['opening'])
                    : null,
                'إجمالي مدين' => isset($statement['totals']['debit'])
                    ? $this->money($statement['totals']['debit'])
                    : null,
                'إجمالي دائن' => isset($statement['totals']['credit'])
                    ? $this->money($statement['totals']['credit'])
                    : null,
                // كشف التاجر بيسمّيه `closing` وكشف المورد `balance`
                'الرصيد الحالي' => $this->money(
                    $statement['closing'] ?? $statement['balance'] ?? 0
                ),
            ]),
        ];
    }

    /**
     * بنود فاتورة.
     *
     * @param  array<string, mixed>  $invoice
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}
     */
    public function invoice(array $invoice, bool $raw = false): array
    {
        $currency = $invoice['currency'] ?? 'EGP';
        $rows = [];

        foreach ($invoice['items'] ?? [] as $item) {
            $rows[] = [
                $item['sku'] ?? '—',
                $item['name'] ?? '—',
                $raw ? (float) ($item['quantity'] ?? 0) : $this->number($item['quantity'] ?? 0),
                $this->amount($item['unitPrice'] ?? 0, $raw),
                $this->amount($item['total'] ?? 0, $raw),
            ];
        }

        $summary = [
            'إجمالي البنود' => $this->money($invoice['subtotal'] ?? 0, $currency),
            'الخصم' => $this->money($invoice['discount'] ?? 0, $currency),
        ];

        if (isset($invoice['tax']) && (float) $invoice['tax'] > 0) {
            $summary['الضريبة'] = $this->money($invoice['tax'], $currency);
        }

        $summary['الإجمالي'] = $this->money($invoice['total'] ?? 0, $currency);
        $summary['المدفوع'] = $this->money($invoice['paid'] ?? 0, $currency);
        $summary['المتبقي'] = $this->money($invoice['remaining'] ?? 0, $currency);

        return [
            'headers' => ['الكود', 'الصنف', 'الكمية', 'السعر', 'الإجمالي'],
            'rows' => $rows,
            'numeric' => [2, 3, 4],
            'summary' => $summary,
        ];
    }

    /**
     * قائمة فواتير بيع — نفس أعمدة الشاشة.
     *
     * @param  iterable<int, array<string, mixed>>  $rows
     * @param  array<string, float|int>  $totals
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}
     */
    public function saleList(iterable $rows, array $totals, bool $raw = false): array
    {
        $output = [];

        foreach ($rows as $row) {
            $output[] = [
                $row['number'],
                $this->date($row['date']),
                $row['customer'] ?? '—',
                $row['warehouse'] ?? '—',
                self::INVOICE_TYPES[$row['type']] ?? $row['type'],
                self::STATUSES[$row['status']] ?? $row['status'],
                $this->amount($row['total'], $raw),
                $this->amount($row['paid'], $raw),
                $this->amount($row['remaining'], $raw),
            ];
        }

        return [
            'headers' => [
                'رقم الفاتورة', 'التاريخ', 'التاجر', 'المخزن',
                'النوع', 'الحالة', 'الإجمالي', 'المحصّل', 'المتبقي',
            ],
            'rows' => $output,
            'numeric' => [6, 7, 8],
            'summary' => [
                'عدد الفواتير' => $this->number($totals['count'] ?? 0),
                'الإجمالي' => $this->money($totals['total'] ?? 0),
                'المحصّل' => $this->money($totals['paid'] ?? 0),
                'المتبقي' => $this->money($totals['outstanding'] ?? 0),
            ],
        ];
    }

    /**
     * قائمة فواتير شراء.
     *
     * فيها عمودين زيادة عن البيع: العملة وسعر الصرف. فواتير الاستيراد
     * بعملة المورد، وإجمالي بـ 900 من غير ما تعرف هي 900 إيه مالوش
     * معنى — فالعملة والمعادل بالجنيه الاتنين لازم يبقوا على الورق.
     *
     * @param  iterable<int, array<string, mixed>>  $rows
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}
     */
    public function purchaseList(iterable $rows, bool $raw = false): array
    {
        $output = [];
        $totalEgp = 0.0;
        $count = 0;

        foreach ($rows as $row) {
            $count++;
            $totalEgp += (float) $row['totalEgp'];

            $output[] = [
                $row['number'],
                $this->date($row['date']),
                $row['supplier'] ?? '—',
                $row['warehouse'] ?? '—',
                self::STATUSES[$row['status']] ?? $row['status'],
                $row['currency'],
                $raw ? (float) $row['exchangeRate'] : $this->number($row['exchangeRate']),
                $raw ? round((float) $row['total'], 2) : $this->money($row['total'], $row['currency']),
                $this->amount($row['totalEgp'], $raw),
                $this->amount($row['landed'], $raw),
            ];
        }

        return [
            'headers' => [
                'رقم الفاتورة', 'التاريخ', 'المورد', 'المخزن', 'الحالة',
                'العملة', 'سعر الصرف', 'الإجمالي', 'الإجمالي بالجنيه', 'مصاريف الوصول',
            ],
            'rows' => $output,
            'numeric' => [6, 7, 8, 9],
            'summary' => [
                'عدد الفواتير' => $this->number($count),
                'الإجمالي بالجنيه' => $this->money($totalEgp),
            ],
        ];
    }

    /**
     * جدول من رد وكيل.
     *
     * الأدوات بتطلّع جداول بصيغة `عمود | عمود`، وإحنا اللي كاتبين
     * الصيغة دي في `FormatsErpData`، فتحليلها راجع مضمون — مش تخمين
     * على نص جاي من الموديل.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}|null
     */
    public function parseAgentTable(string $text): ?array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn (string $line): bool => $line !== '',
        ));

        $headers = null;
        $rows = [];

        foreach ($lines as $line) {
            // سطر الفاصل بين العنوان والبيانات
            if (str_starts_with($line, '—')) {
                continue;
            }

            if (! str_contains($line, '|')) {
                continue;
            }

            $cells = array_map('trim', explode('|', $line));

            if ($headers === null) {
                $headers = $cells;

                continue;
            }

            $rows[] = $cells;
        }

        return $headers === null || $rows === []
            ? null
            : ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function byPeriod(array $data, bool $raw): array
    {
        $rows = [];

        foreach ($data['rows'] ?? [] as $row) {
            $rows[] = [
                $row['period'],
                $raw ? (int) $row['count'] : $this->number($row['count']),
                $this->amount($row['net'] ?? 0, $raw),
                $this->amount($row['tax'] ?? 0, $raw),
                $this->amount($row['total'] ?? 0, $raw),
                $this->amount($row['paid'] ?? 0, $raw),
            ];
        }

        $totals = $data['totals'] ?? [];

        return [
            'headers' => ['الفترة', 'فواتير', 'الصافي', 'الضريبة', 'الإجمالي', 'المحصّل'],
            'rows' => $rows,
            'numeric' => [1, 2, 3, 4, 5],
            'summary' => [
                'عدد الفواتير' => $this->number($totals['count'] ?? 0),
                'الإجمالي' => $this->money($totals['total'] ?? 0),
                'المحصّل' => $this->money($totals['paid'] ?? 0),
            ],
        ];
    }

    /**
     * فاتورة في كل سطر.
     *
     * @param  array<string, mixed>  $data
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>, numeric: array<int, int>, summary: array<string, string>}
     */
    private function byInvoice(array $data, bool $raw, string $partyLabel): array
    {
        $rows = [];

        foreach ($data['rows'] ?? [] as $row) {
            $rows[] = [
                $row['number'],
                $this->date($row['date']),
                $row['party'] ?? '—',
                $row['rep'] ?? '—',
                $this->amount($row['net'] ?? 0, $raw),
                $this->amount($row['tax'] ?? 0, $raw),
                $this->amount($row['total'] ?? 0, $raw),
                $this->amount($row['paid'] ?? 0, $raw),
                $this->amount($row['remaining'] ?? 0, $raw),
                $row['statusLabel'] ?? '—',
            ];
        }

        $totals = $data['totals'] ?? [];

        return [
            'headers' => [
                'رقم الفاتورة', 'التاريخ', $partyLabel, 'المندوب',
                'الصافي', 'الضريبة', 'الإجمالي', 'المحصّل', 'المتبقي', 'الحالة',
            ],
            'rows' => $rows,
            'numeric' => [4, 5, 6, 7, 8],
            'summary' => [
                'عدد الفواتير' => $this->number($totals['count'] ?? 0),
                'الصافي' => $this->money($totals['net'] ?? 0),
                'الإجمالي' => $this->money($totals['total'] ?? 0),
                'المحصّل' => $this->money($totals['paid'] ?? 0),
                'المتبقي' => $this->money($totals['remaining'] ?? 0),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function byProduct(array $data, bool $raw): array
    {
        $rows = [];

        foreach ($data['rows'] ?? [] as $row) {
            $rows[] = [
                $row->sku ?? '—',
                $row->name ?? '—',
                /*
                 * `quantity` مش `units`.
                 *
                 * كان مكتوب `units`، و`salesByProduct` بترجّع `quantity` —
                 * فعمود «المباع» كان بيطلع صفر في كل سطر في الـ PDF
                 * والإكسيل، والتقرير على الشاشة كان مظبوط. `??` كانت
                 * بتبلع الخانة الناقصة في صمت.
                 */
                $raw ? (float) ($row->quantity ?? 0) : $this->number($row->quantity ?? 0),
                $this->amount($row->revenue ?? 0, $raw),
                $this->amount($row->profit ?? 0, $raw),
            ];
        }

        return [
            'headers' => ['الكود', 'الصنف', 'المباع', 'الإيراد', 'الربح'],
            'rows' => $rows,
            'numeric' => [2, 3, 4],
            'summary' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function byCustomer(array $data, bool $raw): array
    {
        $rows = [];

        foreach ($data['rows'] ?? [] as $row) {
            $rows[] = [
                $row->name ?? '—',
                $raw ? (int) ($row->invoices ?? 0) : $this->number($row->invoices ?? 0),
                $this->amount($row->total ?? 0, $raw),
                $this->amount($row->paid ?? 0, $raw),
                $this->amount(($row->total ?? 0) - ($row->paid ?? 0), $raw),
            ];
        }

        return [
            'headers' => ['التاجر', 'فواتير', 'الإجمالي', 'المحصّل', 'المتبقي'],
            'rows' => $rows,
            'numeric' => [1, 2, 3, 4],
            'summary' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function inventory(array $data, bool $raw): array
    {
        $rows = [];

        foreach ($data['rows'] ?? [] as $row) {
            $rows[] = [
                $row['sku'] ?? ($row->sku ?? '—'),
                $row['name'] ?? ($row->name ?? '—'),
                $raw ? (float) ($row['balance'] ?? 0) : $this->number($row['balance'] ?? 0),
                $this->amount($row['cost'] ?? 0, $raw),
                $this->amount($row['value'] ?? 0, $raw),
            ];
        }

        return [
            'headers' => ['الكود', 'الصنف', 'الرصيد', 'التكلفة', 'القيمة'],
            'rows' => $rows,
            'numeric' => [2, 3, 4],
            'summary' => [
                'قيمة المخزون' => $this->money($data['total'] ?? 0),
            ],
        ];
    }

    /** رقم خام للإكسيل، أو نص منسّق للـ PDF */
    private function amount(mixed $value, bool $raw): string|float
    {
        return $raw ? round((float) $value, 2) : $this->money($value);
    }

    private function money(mixed $value, string $currency = 'EGP'): string
    {
        $symbols = ['EGP' => 'ج.م', 'USD' => '$', 'EUR' => '€', 'TRY' => '₺'];

        return number_format((float) $value, 2, '.', ',').' '.($symbols[$currency] ?? $currency);
    }

    private function number(mixed $value): string
    {
        $number = (float) $value;

        return number_format($number, fmod($number, 1) === 0.0 ? 0 : 2, '.', ',');
    }

    private function date(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        $date = Carbon::parse($value);

        return $date->day.' '.self::MONTHS[$date->month - 1].' '.$date->year;
    }

    /** نفس أسماء الشهور اللي في الواجهة (`lib/format.ts`) */
    private const MONTHS = [
        'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
    ];
}
