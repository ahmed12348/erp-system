<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Models\Product;
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
#[Title('الأصناف تحت حد الطلب')]
#[Description('الأصناف اللي رصيدها وصل أو نزل تحت حد الطلب، مرتبة من الأخطر. استعملها لما تتسأل عن النواقص أو إيه اللي محتاج طلبية.')]
class LowStockProducts extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly InventoryService $inventory) {}

    public function handle(Request $request): Response
    {
        $limit = (int) ($request->get('limit') ?: 20);

        $products = $this->inventory->lowStockProducts($limit);

        if ($products->isEmpty()) {
            return Response::text('مفيش أصناف تحت حد الطلب حاليًا.');
        }

        $rows = $products->map(fn (Product $product) => [
            $product->sku,
            $product->name,
            $this->quantity($product->stock_balance, $product->unit),
            $this->quantity($product->min_stock, $product->unit),
            $this->quantity((float) $product->min_stock - (float) $product->stock_balance, $product->unit),
        ])->all();

        return Response::text(
            'عدد الأصناف تحت حد الطلب: '.$this->inventory->lowStockCount()."\n\n"
            .$this->table(['الكود', 'الصنف', 'الرصيد', 'حد الطلب', 'الناقص'], $rows)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('أقصى عدد أصناف يرجع (الافتراضي 20).')
                ->min(1)
                ->max(100),
        ];
    }
}
