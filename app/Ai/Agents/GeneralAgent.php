<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Mcp\Tools\CompanyOverview;
use App\Mcp\Tools\InvoiceLookup;
use App\Mcp\Tools\LowStockProducts;
use App\Mcp\Tools\SalesSummary;
use App\Mcp\Tools\TopDebtors;

/**
 * المجموعة الافتراضية — اللي بيتبعت لما التوجيه مايوصلش لحاجة.
 *
 * ده مش مجال بيتوجّه عليه: الموجّه عمره ما بيرد بـ `general`. ده شبكة
 * الأمان لما السؤال يطلع مش واضح («إيه الأخبار؟») أو لما نداء التوجيه
 * نفسه يقع أو الوقت يضيق عليه.
 *
 * الأدوات مختارة إنها تغطّي أشهر أربع أسئلة في النظام — ناقص إيه، بعنا
 * بكام، مين عليه فلوس، وإيه في الفاتورة دي — زائد لقطة عامة. المستخدم
 * بياخد رد مفيد بدل «ما فهمتش».
 *
 * الصلاحية بتتطبّق فوقها برضه: `AgentRegistry::toolsFor()` بتصفّي على
 * الأدوات اللي دور المستخدم بيوصلها، فالمندوب مش هياخد من هنا أداة
 * مش شايفها في الشات العادي.
 */
class GeneralAgent extends Agent
{
    public function key(): string
    {
        return 'general';
    }

    public function label(): string
    {
        return 'عام';
    }

    public function description(): string
    {
        return 'أسئلة عامة عن حالة الشغل';
    }

    public function instructions(): string
    {
        return <<<'TXT'
            السؤال ده مش واضح على مجال معيّن، فمعاك الأدوات الأساسية بس.

            لو محتاج حاجة أدق من اللي معاك، قول للمستخدم يسأل بصيغة أوضح
            (اسم الصنف، اسم التاجر، أو الفترة) — ماتخمّنش وماتردّش برقم
            من غير أداة.
            TXT;
    }

    public function brief(): string
    {
        return 'عام — لو محتاج حاجة أدق، اطلب من المستخدم يوضّح السؤال.';
    }

    public function starters(): array
    {
        // الأسئلة المقترحة بتتخلط من المجالات الحقيقية، مش من هنا
        return [];
    }

    public function toolClasses(): array
    {
        return [
            LowStockProducts::class,
            SalesSummary::class,
            TopDebtors::class,
            InvoiceLookup::class,
            CompanyOverview::class,
        ];
    }

    public function roles(): array
    {
        return [ErpRole::Admin, ErpRole::Accountant, ErpRole::Sales];
    }
}
