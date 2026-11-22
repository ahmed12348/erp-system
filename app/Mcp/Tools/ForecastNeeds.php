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
#[Title('احتياج الشهر الجاي')]
#[Description('الكميات المتوقّع إنها تنقص في الـ 30 يوم الجايين: متوسط البيع اليومي ناقص الرصيد الحالي. استعملها لما تتسأل «نشتري إيه؟» أو «إيه اللي هيخلص؟».')]
class ForecastNeeds extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request): Response
    {
        $horizon = (int) ($request->get('days') ?: 30);
        $result = $this->analytics->forecastNeeds($horizon);

        /*
         * التحذير ده مهم: توقّع مبني على أسبوعين تاريخ مش توقّع. الأداة
         * بتقوله صراحةً عشان الموديل ما يقدّمهوش كأنه رقم مؤكّد.
         */
        $warning = $result['reliable']
            ? ''
            : "\n\n⚠ تنبيه: التاريخ المتاح {$result['historyDays']} يوم بس (أقل من شهر)، فالتوقّع ده تقريبي جدًا ومينفعش يتبني عليه طلبية كبيرة.";

        if ($result['rows'] === []) {
            return Response::text("مفيش صنف متوقّع ينقص في الـ {$horizon} يوم الجايين — الأرصدة مغطية البيع المتوقّع.".$warning);
        }

        $rows = array_map(fn (array $row): array => [
            $row['sku'],
            $row['name'],
            $this->quantity($row['perDay'], $row['unit']).'/يوم',
            $this->quantity($row['balance'], $row['unit']),
            $this->quantity($row['shortfall'], $row['unit']),
            $row['coverDays'] === null ? '—' : $row['coverDays'].' يوم',
        ], $result['rows']);

        return Response::text(
            "المتوقّع ينقص في الـ {$horizon} يوم الجايين (على أساس {$result['historyDays']} يوم تاريخ):\n\n"
            .$this->table(['الكود', 'الصنف', 'معدّل البيع', 'الرصيد', 'الناقص', 'يكفي'], $rows)
            .$warning
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()
                ->description('الأفق بالأيام (الافتراضي 30).')
                ->min(7)
                ->max(180),
        ];
    }
}
