<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\FormatsErpData;
use App\Services\DashboardService;
use App\Services\SupplierService;
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
#[Title('الصورة العامة للشركة')]
#[Description('لقطة واحدة عن حالة الشركة: مبيعات الشهر ومقارنتها بالشهر اللي فات، المستحق على التجار، المستحق للموردين، قيمة المخزون، وعدد الأصناف تحت حد الطلب. استعملها لأسئلة عامة زي «الشركة عاملة إيه؟».')]
class CompanyOverview extends Tool
{
    use FormatsErpData;

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly SupplierService $suppliers,
    ) {}

    public function handle(Request $request): Response
    {
        $kpis = $this->dashboard->kpis();

        $sales = $kpis['sales'];
        $change = $sales['change'];

        $rows = [
            [
                'مبيعات الشهر لحد النهارده',
                $this->money($sales['value']),
                'أول '.$sales['periodDays'].' يوم من الشهر',
            ],
            [
                'نفس المدة الشهر اللي فات',
                $this->money($sales['previous']),
                $change === null
                    ? 'مفيش مبيعات الشهر اللي فات للمقارنة'
                    : ($change >= 0 ? '+' : '').$change.'%',
            ],
            [
                'المستحق على التجار',
                $this->money($kpis['receivables']['value']),
                $kpis['receivables']['traders'].' تاجر مسجّل',
            ],
            [
                'المستحق للموردين',
                $this->money($this->suppliers->totalPayableEgp()),
                'محوّل للجنيه بآخر سعر صرف',
            ],
            [
                'قيمة المخزون بالتكلفة',
                $this->money($kpis['inventoryValue']['value']),
                '—',
            ],
            [
                'أصناف تحت حد الطلب',
                $this->quantity($kpis['lowStock']['value']),
                $kpis['lowStock']['value'] > 0 ? 'محتاجة طلبية' : 'كله فوق الحد',
            ],
        ];

        return Response::text(
            "الصورة العامة للشركة النهارده\n\n"
            .$this->table(['المؤشر', 'القيمة', 'ملاحظة'], $rows)
        );
    }

    /**
     * مفيش مدخلات — اللقطة دايمًا عن الشهر الحالي.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
