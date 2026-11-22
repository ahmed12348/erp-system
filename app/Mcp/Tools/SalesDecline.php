<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Services\AnalyticsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
#[Title('تحليل نزول المبيعات')]
#[Description('بيقارن فترتين ويطلّع أكبر الانخفاضات بالصنف وبالتاجر وبالمندوب مع بعض. استعملها لما تتسأل «المبيعات نزلت ليه؟» أو «إيه اللي اتغيّر عن الشهر اللي فات؟».')]
class SalesDecline extends Tool
{
    use FormatsErpData;

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request): Response
    {
        [$from, $to] = $this->range($request->get('from'), $request->get('to'));

        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $length = (int) $start->diffInDays($end) + 1;

        // الفترة المقارنة: اللي قبلها بنفس الطول، إلا لو المستخدم حدّدها
        $previousTo = filled($request->get('compare_to'))
            ? Carbon::parse($request->get('compare_to'))
            : $start->copy()->subDay();

        $previousFrom = filled($request->get('compare_from'))
            ? Carbon::parse($request->get('compare_from'))
            : $previousTo->copy()->subDays($length - 1);

        $result = $this->analytics->salesDecline(
            $from,
            $to,
            $previousFrom->toDateString(),
            $previousTo->toDateString(),
        );

        $headline = $result['change'] === null
            ? 'مفيش مبيعات في الفترة السابقة نقارن بيها.'
            : ($result['change'] >= 0
                ? 'المبيعات زادت '.$result['change'].'%'
                : 'المبيعات نزلت '.abs($result['change']).'%');

        $sections = [
            $headline.': '.$this->money($result['current']['total'])
                .' مقابل '.$this->money($result['previous']['total'])
                .' ('.$previousFrom->toDateString().' لـ '.$previousTo->toDateString().').',
        ];

        foreach ([
            'byProduct' => 'أكبر النزول بالصنف',
            'byCustomer' => 'أكبر النزول بالتاجر',
            'byRep' => 'أكبر النزول بالمندوب',
        ] as $key => $title) {
            if ($result[$key] === []) {
                continue;
            }

            $rows = array_map(fn (array $row): array => [
                $row['label'],
                $this->money($row['before']),
                $this->money($row['after']),
                $this->money($row['delta']),
                $row['change'] === null ? '—' : $row['change'].'%',
            ], $result[$key]);

            $sections[] = $title.":\n".$this->table(['الاسم', 'قبل', 'بعد', 'الفرق', 'النسبة'], $rows);
        }

        return Response::text(implode("\n\n", $sections));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('بداية الفترة الحالية YYYY-MM-DD (الافتراضي آخر 30 يوم).'),
            'to' => $schema->string()->description('نهاية الفترة الحالية YYYY-MM-DD.'),
            'compare_from' => $schema->string()->description('بداية فترة المقارنة — سيبها فاضية عشان ياخد الفترة اللي قبلها بنفس الطول.'),
            'compare_to' => $schema->string()->description('نهاية فترة المقارنة.'),
        ];
    }
}
