<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Mcp\Tools\BestCustomers;
use App\Mcp\Tools\RepsComparison;
use App\Mcp\Tools\SalesBreakdown;
use App\Mcp\Tools\SalesDecline;
use App\Mcp\Tools\SalesSummary;
use App\Mcp\Tools\TopDebtors;
use App\Mcp\Tools\TopProducts;
use App\Mcp\Tools\TraderBalance;

/**
 * وكيل المبيعات — الأداء والتجار والمندوبين.
 */
class SalesAgent extends Agent
{
    public function key(): string
    {
        return 'sales';
    }

    public function label(): string
    {
        return 'المبيعات';
    }

    public function description(): string
    {
        return 'الأداء، التجار، المندوبين، وأسباب النزول';
    }

    public function instructions(): string
    {
        return <<<'TXT'
            تخصصك المبيعات: الأداء، التجار، المندوبين، وإيه اللي اتغيّر.

            لما تتكلم عن فترة، قارنها باللي قبلها لو الأداة رجّعت المقارنة —
            رقم لوحده مابيقولش حاجة، النسبة هي اللي بتقول.

            لما المبيعات تنزل، ماتكتفيش بإنك تقول نزلت: استعمل أداة التحليل
            وقول نزلت فين بالظبط — أنهي صنف وأنهي تاجر وأنهي مندوب.

            عمولة المندوب بتتحسب على المحصّل مش على المبيعات — خد بالك لما
            تقارن بينهم.

            رصيد التاجر الموجب يعني عليه فلوس للشركة.
            TXT;
    }

    public function starters(): array
    {
        return [
            'مبيعات الشهر ده عاملة إيه عن اللي فات؟',
            'المبيعات نزلت ليه؟',
            'مين أحسن التجار وأسرعهم في السداد؟',
            'قارن لي أداء المندوبين',
        ];
    }

    public function toolClasses(): array
    {
        return [
            SalesSummary::class,
            SalesBreakdown::class,
            SalesDecline::class,
            TopProducts::class,
            BestCustomers::class,
            RepsComparison::class,
            TraderBalance::class,
            TopDebtors::class,
        ];
    }

    public function roles(): array
    {
        return [ErpRole::Admin, ErpRole::Accountant, ErpRole::Sales];
    }
}
