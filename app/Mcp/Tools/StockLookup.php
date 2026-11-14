<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Services\InventoryService;
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
#[Title('رصيد صنف في المخازن')]
#[Description('رصيد صنف (أو أصناف) في كل مخزن على حدة، مع التكلفة وسعر البيع وحد الطلب. ابحث بالاسم أو الكود.')]
class StockLookup extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly InventoryService $inventory) {}

    public function handle(Request $request): Response
    {
        $term = trim((string) $request->get('search'));

        $rows = $this->inventory->stockRows(['search' => $term])->take(10);

        if ($rows->isEmpty()) {
            return Response::text("مفيش صنف اسمه أو كوده يشبه «{$term}».");
        }

        $blocks = $rows->map(function (array $row) {
            $warehouses = collect($row['warehouses'])
                ->map(fn (array $warehouse) => $warehouse['name'].': '.$this->quantity($warehouse['qty']))
                ->implode(' · ');

            return implode("\n", [
                $row['sku'].' — '.$row['name'].($row['isLow'] ? ' ⚠ تحت حد الطلب' : ''),
                'التصنيف: '.($row['category'] ?? '—'),
                'الرصيد الكلي: '.$this->quantity($row['total'], $row['unit'])
                    .' · حد الطلب: '.$this->quantity($row['minStock'], $row['unit']),
                'المخازن: '.$warehouses,
                'التكلفة: '.$this->money($row['cost']).' · سعر البيع: '.$this->money($row['salePrice'])
                    .' · قيمة الرصيد: '.$this->money($row['value']),
            ]);
        })->implode("\n\n");

        return Response::text($blocks);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('اسم الصنف أو جزء منه، أو الكود (SKU).')
                ->required(),
        ];
    }
}
