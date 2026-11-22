<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Services\AnalyticsService;
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
#[Title('تفصيل المبيعات')]
#[Description('المبيعات مقسّمة باليوم أو بالأسبوع أو بالشهر، ومعاها نسبة التغيّر عن الفترة اللي قبلها بنفس الطول. استعملها لما تتسأل عن أداء فترة أو مقارنتها باللي قبلها.')]
class SalesBreakdown extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));
        $groupBy = in_array($request->get('group_by'), ['day', 'week', 'month'], true)
            ? $request->get('group_by')
            : 'day';

        $result = $this->analytics->salesBreakdown($from, $to, $groupBy);

        if ($result['rows'] === []) {
            return Response::text("مفيش مبيعات مؤكّدة من {$from} لـ {$to}.");
        }

        $rows = array_map(fn (array $row): array => [
            $row['period'],
            (string) $row['count'],
            $this->money($row['total']),
        ], $result['rows']);

        $change = $result['change'] === null
            ? 'مفيش مبيعات في الفترة السابقة نقارن بيها.'
            : ($result['change'] >= 0 ? 'زيادة ' : 'نزول ').abs($result['change']).'% عن الفترة السابقة ('
                .$this->money($result['previousTotal']).' من '.$result['previousFrom'].' لـ '.$result['previousTo'].').';

        return Response::text(
            'إجمالي المبيعات من '.$from.' لـ '.$to.': '.$this->money($result['total'])
            .' في '.$result['count']." فاتورة.\n".$change."\n\n"
            .$this->table(['الفترة', 'فواتير', 'الإجمالي'], $rows)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('بداية الفترة YYYY-MM-DD (الافتراضي آخر 30 يوم).'),
            'to' => $schema->string()->description('نهاية الفترة YYYY-MM-DD.'),
            'group_by' => $schema->string()->description('التقسيم: day أو week أو month (الافتراضي day).'),
        ];
    }
}
