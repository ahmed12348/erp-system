<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Models\Invoice;
use App\Models\InvoiceItem;
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
#[Title('بيانات فاتورة')]
#[Description('تفاصيل فاتورة برقمها: الطرف، التاريخ، البنود، الإجماليات، السداد، وحالة الفاتورة الإلكترونية. بيشتغل مع فواتير البيع والشراء.')]
class InvoiceLookup extends Tool
{
    use FormatsErpData;

    private const TYPES = [
        Invoice::TYPE_SALE => 'فاتورة بيع',
        Invoice::TYPE_PURCHASE => 'فاتورة شراء',
        Invoice::TYPE_SALE_RETURN => 'مرتجع بيع',
        Invoice::TYPE_PURCHASE_RETURN => 'مرتجع شراء',
    ];

    private const ETA = [
        Invoice::ETA_PENDING => 'في الانتظار',
        Invoice::ETA_SENT => 'مُرسلة',
        Invoice::ETA_SUBMITTED => 'مُقدّمة',
        Invoice::ETA_VALID => 'مقبولة',
        Invoice::ETA_REJECTED => 'مرفوضة',
    ];

    public function handle(Request $request): Response
    {
        $number = trim((string) $request->get('number'));

        $invoice = Invoice::query()
            ->with(['items.product:id,sku,name,unit', 'customer:id,name,city', 'supplier:id,name,country', 'warehouse:id,name'])
            ->where('number', $number)
            ->orWhere('number', 'like', '%'.$number.'%')
            ->first();

        if (! $invoice) {
            return Response::text("مفيش فاتورة برقم «{$number}».");
        }

        $currency = $invoice->currency;

        $header = [
            (self::TYPES[$invoice->type] ?? $invoice->type).' رقم '.$invoice->number,
            'التاريخ: '.$invoice->date->toDateString()
                .($invoice->due_date ? ' · الاستحقاق: '.$invoice->due_date->toDateString() : ''),
            'الطرف: '.($invoice->customer?->name ?? $invoice->supplier?->name ?? '—'),
            'المخزن: '.($invoice->warehouse?->name ?? '—'),
            'الحالة: '.$invoice->status,
        ];

        if ($currency !== 'EGP') {
            $header[] = 'العملة: '.$currency.' · سعر الصرف: '.(float) $invoice->exchange_rate
                .' · الإجمالي بالجنيه: '.$this->money((float) $invoice->total * (float) $invoice->exchange_rate);
        }

        if ($invoice->type === Invoice::TYPE_SALE) {
            $header[] = 'الفاتورة الإلكترونية: '
                .($invoice->eta_status ? (self::ETA[$invoice->eta_status] ?? $invoice->eta_status) : 'ماتبعتتش')
                .($invoice->eta_uuid ? ' · UUID: '.$invoice->eta_uuid : '');
        }

        $rows = $invoice->items->map(fn (InvoiceItem $item) => [
            $item->product?->sku ?? '—',
            $item->product?->name ?? '—',
            $this->quantity($item->quantity, $item->product?->unit),
            $this->money($item->unit_price, $currency),
            $this->money($item->total, $currency),
        ])->all();

        $totals = [
            'الصافي: '.$this->money($invoice->subtotal, $currency),
            'الخصم: '.$this->money($invoice->discount, $currency),
            'الضريبة: '.$this->money($invoice->tax, $currency),
            'الإجمالي: '.$this->money($invoice->total, $currency),
            'المدفوع: '.$this->money($invoice->paid, $currency),
            'المتبقي: '.$this->money($invoice->remaining, $currency),
        ];

        if ($invoice->type === Invoice::TYPE_PURCHASE) {
            $landed = (float) $invoice->shipping_cost + (float) $invoice->customs_cost + (float) $invoice->clearance_cost;
            $totals[] = 'مصاريف الاستيراد (شحن + جمارك + تخليص): '.$this->money($landed);
        }

        return Response::text(
            implode("\n", $header)."\n\n"
            .$this->table(['الكود', 'الصنف', 'الكمية', 'السعر', 'الإجمالي'], $rows)."\n\n"
            .implode(' · ', $totals)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'number' => $schema->string()
                ->description('رقم الفاتورة، مثلاً S-2026-00093 أو P-2026-00018.')
                ->required(),
        ];
    }
}
