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
#[Title('أكتر الأصناف مبيعًا')]
#[Description('الأصناف الأعلى مبيعًا في فترة، بالكمية والقيمة وهامش الربح. ترتيب افتراضي بقيمة المبيعات.')]
class TopProducts extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly ReportService $reports) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));
        $limit = (int) ($request->get('limit') ?: 10);
        $sort = $request->get('sort_by') === 'quantity' ? 'quantity' : 'revenue';

        $products = $this->reports->salesByProduct($from, $to, 200)
            ->sortByDesc(fn ($row) => (float) $row->{$sort})
            ->take($limit);

        if ($products->isEmpty()) {
            return Response::text("مفيش مبيعات في الفترة من {$from} إلى {$to}.");
        }

        $rows = $products->map(fn ($row) => [
            $row->sku,
            $row->name,
            $row->category ?? '—',
            $this->quantity($row->quantity, $row->unit),
            $this->money($row->revenue),
            $this->money($row->profit).' ('.$row->margin.'%)',
        ])->values()->all();

        return Response::text(
            "أكتر {$limit} صنف مبيعًا من {$from} إلى {$to} (الترتيب بـ "
            .($sort === 'quantity' ? 'الكمية' : 'القيمة').")\n\n"
            .$this->table(['الكود', 'الصنف', 'التصنيف', 'الكمية', 'المبيعات', 'الربح'], $rows)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('تاريخ البداية YYYY-MM-DD (الافتراضي: من 30 يوم).'),
            'to' => $schema->string()->description('تاريخ النهاية YYYY-MM-DD (الافتراضي: النهارده).'),
            'limit' => $schema->integer()->description('عدد الأصناف (الافتراضي 10).')->min(1)->max(50),
            'sort_by' => $schema->string()
                ->description('الترتيب: revenue بقيمة المبيعات أو quantity بالكمية.')
                ->enum(['revenue', 'quantity']),
        ];
    }
}
