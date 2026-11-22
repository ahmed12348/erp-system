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
#[Title('أحسن التجار')]
#[Description('أحسن التجار في فترة — بالمبيعات أو بالمحصّل أو بسرعة السداد. استعملها لما تتسأل مين أهم التجار أو مين بيدفع بسرعة.')]
class BestCustomers extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));
        $by = in_array($request->get('by'), ['sales', 'collected', 'speed'], true)
            ? $request->get('by')
            : 'sales';

        $rows = $this->analytics->bestCustomers($from, $to, $by, (int) ($request->get('limit') ?: 10));

        if ($rows === []) {
            return Response::text("مفيش مبيعات لتجار في الفترة من {$from} لـ {$to}.");
        }

        $table = array_map(fn (array $row): array => [
            $row['name'],
            (string) $row['invoices'],
            $this->money($row['sales']),
            $this->money($row['collected']),
            $row['paymentDays'] === null ? '—' : $row['paymentDays'].' يوم',
        ], $rows);

        return Response::text(
            "أحسن التجار من {$from} لـ {$to}:\n\n"
            .$this->table(['التاجر', 'فواتير', 'المبيعات', 'المحصّل', 'متوسط السداد'], $table)
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
            'by' => $schema->string()->description('الترتيب: sales أو collected أو speed (الافتراضي sales).'),
            'limit' => $schema->integer()->description('أقصى عدد تجار (الافتراضي 10).')->min(1)->max(50),
        ];
    }
}
