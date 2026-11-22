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
#[Title('الأصناف سريعة الحركة')]
#[Description('أكتر الأصناف حركة في فترة — بالكمية أو بالإيراد أو بمعدّل دوران المخزون. استعملها لما تتسأل عن الأصناف اللي ماشية أو اللي محتاجة تزويد.')]
class FastMovingProducts extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));
        $by = in_array($request->get('by'), ['units', 'revenue', 'turnover'], true)
            ? $request->get('by')
            : 'units';

        $rows = $this->analytics->fastMoving($from, $to, $by, (int) ($request->get('limit') ?: 15));

        if ($rows === []) {
            return Response::text("مفيش مبيعات في الفترة من {$from} لـ {$to}.");
        }

        $table = array_map(fn (array $row): array => [
            $row['sku'],
            $row['name'],
            $this->quantity($row['units'], $row['unit']),
            $this->money($row['revenue']),
            $row['turnover'] === null ? 'خلص' : $row['turnover'].'×',
        ], $rows);

        return Response::text(
            "أكتر الأصناف حركة من {$from} لـ {$to}:\n\n"
            .$this->table(['الكود', 'الصنف', 'المباع', 'الإيراد', 'الدوران'], $table)
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
            'by' => $schema->string()->description('الترتيب: units أو revenue أو turnover (الافتراضي units).'),
            'limit' => $schema->integer()->description('أقصى عدد أصناف (الافتراضي 15).')->min(1)->max(50),
        ];
    }
}
