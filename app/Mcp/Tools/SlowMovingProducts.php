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
#[Title('الأصناف الراكدة')]
#[Description('الأصناف اللي عندها رصيد وما اتباعتش من مدة، ومعاها قيمة الفلوس المحبوسة فيها. استعملها لما تتسأل عن البضاعة الواقفة أو الفلوس النايمة في المخزن.')]
class SlowMovingProducts extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request): Response
    {
        $days = (int) ($request->get('days') ?: 60);
        $result = $this->analytics->slowMoving($days, (int) ($request->get('limit') ?: 20));

        if ($result['rows'] === []) {
            return Response::text("مفيش أصناف راكدة أكتر من {$days} يوم — كل اللي في المخزن بيتحرّك.");
        }

        $rows = array_map(fn (array $row): array => [
            $row['sku'],
            $row['name'],
            $this->quantity($row['balance'], $row['unit']),
            $this->money($row['value']),
            $row['idleDays'] === null ? 'عمره ما اتباع' : $row['idleDays'].' يوم',
        ], $result['rows']);

        return Response::text(
            'فلوس نايمة في أصناف راكدة أكتر من '.$days.' يوم: '.$this->money($result['tied'])."\n\n"
            .$this->table(['الكود', 'الصنف', 'الرصيد', 'القيمة', 'آخر بيعة'], $rows)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()
                ->description('عدد الأيام من غير بيع عشان الصنف يتحسب راكد (الافتراضي 60).')
                ->min(7)
                ->max(365),
            'limit' => $schema->integer()
                ->description('أقصى عدد أصناف يرجع (الافتراضي 20).')
                ->min(1)
                ->max(50),
        ];
    }
}
