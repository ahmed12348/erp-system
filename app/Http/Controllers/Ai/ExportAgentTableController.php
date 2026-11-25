<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Services\ExportBuilder;
use App\Services\PdfService;
use App\Services\SpreadsheetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تحميل جدول من رد وكيل.
 *
 * الأدوات بتطلّع جداول بصيغة `عمود | عمود` من `FormatsErpData` —
 * الصيغة دي بتاعتنا إحنا مش بتاعة الموديل، فتحليلها راجع مضمون.
 * الواجهة بتبعت نص الرد زي ما هو، وإحنا بنحوّله لجدول.
 *
 * مفيش بيانات بتتولد هنا: اللي بيتصدّر هو اللي المستخدم شايفه قدامه
 * على الشاشة بالظبط.
 */
class ExportAgentTableController extends Controller
{
    public function __construct(
        private readonly ExportBuilder $builder,
        private readonly PdfService $pdf,
        private readonly SpreadsheetService $excel,
    ) {}

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
            'title' => ['nullable', 'string', 'max:120'],
            'format' => ['nullable', 'in:pdf,excel'],
        ]);

        $table = $this->builder->parseAgentTable($validated['text']);

        if ($table === null) {
            return response()->json([
                'message' => 'الرد ده مفيهوش جدول ينفع يتحمّل.',
            ], 422);
        }

        $title = $validated['title'] ?? 'نتيجة من المساعد';

        if (($validated['format'] ?? 'pdf') === 'excel') {
            return $this->excel->download($title, $table['headers'], $table['rows']);
        }

        return $this->pdf->download('pdf.table', [
            'title' => $title,
            'company' => CompanyProfile::current()->present(),
            'range' => null,
            'printedAt' => now()->toDateString(),
            'headers' => $table['headers'],
            'rows' => $table['rows'],
            // الأعمدة اللي كلها أرقام بتتحاذى على الشمال
            'numeric' => $this->numericColumns($table['rows']),
            'summary' => [],
        ], $title);
    }

    /**
     * بيكتشف أعمدة الأرقام من المحتوى نفسه.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, int>
     */
    private function numericColumns(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $columns = range(0, count($rows[0]) - 1);

        return array_values(array_filter($columns, function (int $column) use ($rows): bool {
            foreach ($rows as $row) {
                $cell = trim((string) ($row[$column] ?? ''));

                if ($cell === '' || $cell === '—') {
                    continue;
                }

                // رقم، يمكن معاه رمز عملة أو علامة نسبة
                if (preg_match('/^-?[\d,]+(\.\d+)?\s*(ج\.م|\$|€|₺|%|×|يوم)?$/u', $cell) !== 1) {
                    return false;
                }
            }

            return true;
        }));
    }
}
