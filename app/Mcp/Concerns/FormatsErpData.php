<?php

namespace App\Mcp\Concerns;

/**
 * تنسيق موحّد لمخرجات أدوات الـ MCP.
 *
 * الناتج نص عربي بأرقام لاتينية — نفس القاعدة اللي في الواجهة — عشان
 * اللي يقرا الرد (كلود أو المستخدم) يلاقي نفس الأرقام اللي على الشاشة.
 */
trait FormatsErpData
{
    protected function money(float|string|null $value, string $currency = 'EGP'): string
    {
        $symbols = ['EGP' => 'ج.م', 'USD' => '$', 'EUR' => '€', 'TRY' => '₺'];

        return number_format((float) $value, 2, '.', ',').' '.($symbols[$currency] ?? $currency);
    }

    protected function quantity(float|string|null $value, ?string $unit = null): string
    {
        $number = (float) $value;
        $formatted = number_format($number, fmod($number, 1) === 0.0 ? 0 : 2, '.', ',');

        return $unit ? $formatted.' '.$unit : $formatted;
    }

    /**
     * جدول نصي بسيط — أوضح من JSON لما الرد بيتقرا كنص.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    protected function table(array $headers, array $rows): string
    {
        if ($rows === []) {
            return 'مفيش نتائج.';
        }

        $lines = [implode(' | ', $headers), str_repeat('—', 40)];

        foreach ($rows as $row) {
            $lines[] = implode(' | ', $row);
        }

        return implode("\n", $lines);
    }

    /** الفترة الافتراضية: آخر 30 يوم */
    protected function range(mixed $from, mixed $to): array
    {
        return [
            filled($from) ? (string) $from : now()->subDays(30)->toDateString(),
            filled($to) ? (string) $to : now()->toDateString(),
        ];
    }
}
