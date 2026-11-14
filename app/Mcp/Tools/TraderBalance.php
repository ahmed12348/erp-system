<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Models\Customer;
use App\Services\CustomerService;
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
#[Title('رصيد تاجر')]
#[Description('رصيد تاجر معيّن وحالته مع حد الائتمان: إجمالي مبيعاته، المحصّل منه، والمتبقي عليه. ابحث بالاسم أو جزء منه.')]
class TraderBalance extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly CustomerService $customers) {}

    public function handle(Request $request): Response
    {
        $term = trim((string) $request->get('name'));

        $matches = Customer::query()
            ->where('name', 'like', '%'.$term.'%')
            ->orWhere('phone', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->limit(10)
            ->get();

        if ($matches->isEmpty()) {
            return Response::text("مفيش تاجر اسمه يشبه «{$term}».");
        }

        // أكتر من نتيجة: نرجّع القايمة عشان المستخدم يحدّد
        if ($matches->count() > 1) {
            return Response::text(
                "في أكتر من تاجر بيطابقوا «{$term}»:\n\n"
                .$this->table(
                    ['التاجر', 'المحافظة', 'الرصيد'],
                    $matches->map(fn (Customer $customer) => [
                        $customer->name,
                        $customer->city ?? '—',
                        $this->money($this->customers->balanceFor($customer)),
                    ])->all()
                )
            );
        }

        $customer = $matches->first();
        $summary = $this->customers->summary($customer);

        $lines = [
            'التاجر: '.$customer->name,
            'المحافظة: '.($customer->city ?? '—'),
            'التليفون: '.($customer->phone ?? '—'),
            'إجمالي المبيعات: '.$this->money($summary['sales']),
            'المرتجعات: '.$this->money($summary['returns']),
            'المحصّل: '.$this->money($summary['paid']),
            'الرصيد المستحق: '.$this->money($summary['balance']),
            'عدد الفواتير: '.$summary['invoiceCount'],
        ];

        if ($summary['creditLimit'] !== null) {
            $lines[] = 'حد الائتمان: '.$this->money($summary['creditLimit'])
                .' (مستخدم '.$summary['creditUsage'].'%)';
            $lines[] = $summary['overLimit']
                ? '⚠ التاجر تعدّى حد الائتمان.'
                : 'التاجر لسه تحت حد الائتمان.';
        }

        return Response::text(implode("\n", $lines));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('اسم التاجر أو جزء منه، أو رقم تليفونه.')
                ->required(),
        ];
    }
}
