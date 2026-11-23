<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Mcp\Tools\ForecastNeeds;
use App\Mcp\Tools\InvoiceLookup;
use App\Mcp\Tools\LowStockProducts;
use App\Mcp\Tools\SlowMovingProducts;
use App\Mcp\Tools\StockLookup;

/**
 * وكيل المشتريات — نشتري إيه وبكام.
 *
 * أدواته متداخلة مع المخازن عن قصد: قرار الشراء بيتبني على النواقص
 * والتوقّع، بس السؤال هنا شكله تاني — «أطلب إيه من مين» مش «عندي إيه».
 */
class PurchasingAgent extends Agent
{
    public function key(): string
    {
        return 'purchasing';
    }

    public function label(): string
    {
        return 'المشتريات';
    }

    public function description(): string
    {
        return 'نطلب إيه، وفواتير الموردين';
    }

    public function instructions(): string
    {
        return <<<'TXT'
            تخصصك الشراء: نطلب إيه، وإيه اللي اشتريناه قبل كده وبكام.

            لما توصّي بطلبية، ابدأ بالناقص الفعلي من أداة التوقّع، وحذّر لو
            الأداة قالت إن التاريخ قصير.

            ماتوصّيش بشراء صنف الأداة قالت إنه راكد — ده بيزوّد الفلوس
            النايمة بدل ما يقلّلها.

            فواتير الشراء بعملة المورد ومعاها سعر صرف. لما تقول رقم، قول
            بعملته زي ما الأداة رجّعته، وماتحوّلش بنفسك.
            TXT;
    }

    public function brief(): string
    {
        return 'الشراء — ماتوصّيش بشراء صنف الأداة قالت إنه راكد، وفواتير '
            .'الشراء بعملة المورد فقول الرقم بعملته وماتحوّلش.';
    }

    public function starters(): array
    {
        return [
            'أطلب إيه الشهر الجاي؟',
            'إيه الأصناف اللي تحت حد الطلب؟',
            'إيه البضاعة الراكدة اللي ماتتطلبش تاني؟',
            'وريني تفاصيل فاتورة شراء',
        ];
    }

    public function toolClasses(): array
    {
        return [
            ForecastNeeds::class,
            LowStockProducts::class,
            SlowMovingProducts::class,
            StockLookup::class,
            InvoiceLookup::class,
        ];
    }

    public function roles(): array
    {
        return [ErpRole::Admin, ErpRole::Accountant];
    }
}
