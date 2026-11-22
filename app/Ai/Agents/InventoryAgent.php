<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Mcp\Tools\FastMovingProducts;
use App\Mcp\Tools\ForecastNeeds;
use App\Mcp\Tools\LowStockProducts;
use App\Mcp\Tools\SlowMovingProducts;
use App\Mcp\Tools\StockLookup;

/**
 * وكيل المخازن — النواقص والركود والتوقّعات.
 */
class InventoryAgent extends Agent
{
    public function key(): string
    {
        return 'inventory';
    }

    public function label(): string
    {
        return 'المخازن';
    }

    public function description(): string
    {
        return 'النواقص، الراكد، والمتوقّع يخلص';
    }

    public function instructions(): string
    {
        return <<<'TXT'
            تخصصك المخزن: إيه اللي ناقص، إيه اللي راكد، وإيه اللي هيخلص.

            لما تلاقي صنف تحت حد الطلب، قول الناقص كام وكفايته كام يوم لو
            الأداة رجّعت الرقمين.

            الركود فلوس نايمة — لما تتكلم عن صنف راكد اربطه بقيمته بالجنيه،
            ده اللي بيخلّي القرار واضح.

            لو أداة التوقّع قالت إن التاريخ أقل من شهر، **لازم** تقول للمستخدم
            إن الرقم تقريبي ومينفعش يبني عليه طلبية كبيرة. ماتخبّيش التحذير ده.
            TXT;
    }

    public function starters(): array
    {
        return [
            'إيه الأصناف اللي قربت تخلص؟',
            'إيه البضاعة الراكدة وكام فلوس نايمة فيها؟',
            'أشتري إيه الشهر الجاي؟',
            'إيه أكتر صنف بيتحرّك دلوقتي؟',
        ];
    }

    public function toolClasses(): array
    {
        return [
            LowStockProducts::class,
            StockLookup::class,
            SlowMovingProducts::class,
            FastMovingProducts::class,
            ForecastNeeds::class,
        ];
    }

    public function roles(): array
    {
        return [ErpRole::Admin, ErpRole::Accountant, ErpRole::Sales];
    }
}
