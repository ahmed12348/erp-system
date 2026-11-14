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
#[Title('أكبر المديونيات')]
#[Description('التجار اللي عليهم أكبر أرصدة مستحقة، مع نسبة استخدام حد الائتمان. استعملها لأسئلة زي «مين عليه فلوس» أو «أكبر المديونيات».')]
class TopDebtors extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly CustomerService $customers) {}

    public function handle(Request $request): Response
    {
        $limit = (int) ($request->get('limit') ?: 10);

        $debtors = $this->customers->withBalance()
            ->orderByDesc('balance')
            ->limit($limit)
            ->get()
            ->filter(fn (Customer $customer) => (float) $customer->balance > 0);

        if ($debtors->isEmpty()) {
            return Response::text('مفيش أرصدة مستحقة على التجار.');
        }

        $rows = $debtors->map(function (Customer $customer) {
            $limitValue = $customer->credit_limit ? (float) $customer->credit_limit : null;
            $balance = (float) $customer->balance;

            return [
                $customer->name,
                $customer->city ?? '—',
                $this->money($balance),
                $limitValue ? $this->money($limitValue) : 'بدون حد',
                $limitValue && $limitValue > 0
                    ? round(($balance / $limitValue) * 100, 1).'%'.($balance > $limitValue ? ' ⚠' : '')
                    : '—',
            ];
        })->all();

        return Response::text(
            'إجمالي المستحق على كل التجار: '.$this->money($this->customers->totalReceivables())."\n\n"
            .$this->table(['التاجر', 'المحافظة', 'الرصيد', 'حد الائتمان', 'الاستخدام'], $rows)
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('عدد التجار اللي يرجعوا (الافتراضي 10).')
                ->min(1)
                ->max(50),
        ];
    }
}
