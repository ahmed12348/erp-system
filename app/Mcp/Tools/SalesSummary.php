<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Services\ReportService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
#[Title('ملخّص المبيعات')]
#[Description('إجمالي المبيعات في فترة، مقسّمة باليوم أو بالشهر: عدد الفواتير، الصافي، الضريبة، الإجمالي، والمحصّل. الفترة الافتراضية آخر 30 يوم.')]
class SalesSummary extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly ReportService $reports) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));
        $groupBy = $request->get('group_by') === 'month' ? 'month' : 'day';

        $report = $this->reports->salesByPeriod($from, $to, $groupBy);
        $totals = $report['totals'];

        $rows = collect($report['rows'])->take(60)->map(fn (array $row) => [
            $row['period'],
            (string) $row['count'],
            $this->money($row['net']),
            $this->money($row['tax']),
            $this->money($row['total']),
            $this->money($row['paid']),
        ])->all();

        return Response::text(
            "المبيعات من {$from} إلى {$to}\n"
            .'عدد الفواتير: '.(int) $totals['count']
            .' · الصافي: '.$this->money($totals['net'])
            .' · الضريبة: '.$this->money($totals['tax'])
            .' · الإجمالي: '.$this->money($totals['total'])
            .' · المحصّل: '.$this->money($totals['paid'])
            .' · المتبقي: '.$this->money($totals['total'] - $totals['paid'])."\n\n"
            .$this->table(
                [$groupBy === 'month' ? 'الشهر' : 'اليوم', 'فواتير', 'الصافي', 'الضريبة', 'الإجمالي', 'المحصّل'],
                $rows
            )
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()
                ->description('تاريخ البداية بصيغة YYYY-MM-DD (الافتراضي: من 30 يوم).'),
            'to' => $schema->string()
                ->description('تاريخ النهاية بصيغة YYYY-MM-DD (الافتراضي: النهارده).'),
            'group_by' => $schema->string()
                ->description('التقسيم: day أو month (الافتراضي day).')
                ->enum(['day', 'month']),
        ];
    }
}
