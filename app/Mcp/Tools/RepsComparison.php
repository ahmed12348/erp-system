<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Services\SalesRepService;
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
#[Title('مقارنة المندوبين')]
#[Description('مقارنة المندوبين في فترة: المبيعات والمحصّل والمستحق والعمولة وعدد الفواتير. استعملها لما تتسأل عن أداء المندوبين أو مين أحسن واحد فيهم.')]
class RepsComparison extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly SalesRepService $reps) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));

        $rows = $this->reps->performance(['from' => $from, 'to' => $to]);

        if ($rows->isEmpty()) {
            return Response::text('مفيش مندوبين مسجّلين.');
        }

        $table = $rows->map(fn (array $rep): array => [
            $rep['name'],
            (string) $rep['invoices'],
            $this->money($rep['netSales']),
            $this->money($rep['collected']),
            $this->money($rep['outstanding']),
            $this->money($rep['commission']),
        ])->all();

        return Response::text(
            "أداء المندوبين من {$from} لـ {$to}:\n\n"
            .$this->table(['المندوب', 'فواتير', 'صافي المبيعات', 'المحصّل', 'المستحق', 'العمولة'], $table)
            ."\n\nالعمولة بتتحسب على المحصّل مش على المبيعات."
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
        ];
    }
}
