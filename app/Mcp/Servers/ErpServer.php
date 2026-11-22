<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BestCustomers;
use App\Mcp\Tools\FastMovingProducts;
use App\Mcp\Tools\ForecastNeeds;
use App\Mcp\Tools\InvoiceLookup;
use App\Mcp\Tools\LastLogin;
use App\Mcp\Tools\LowStockProducts;
use App\Mcp\Tools\RecentActivity;
use App\Mcp\Tools\RepsComparison;
use App\Mcp\Tools\SalesBreakdown;
use App\Mcp\Tools\SalesDecline;
use App\Mcp\Tools\SalesSummary;
use App\Mcp\Tools\SlowMovingProducts;
use App\Mcp\Tools\StockLookup;
use App\Mcp\Tools\TopDebtors;
use App\Mcp\Tools\TopProducts;
use App\Mcp\Tools\TraderBalance;
use App\Mcp\Tools\WhoDid;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('نظام إدارة التوزيع')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
    سيرفر قراءة فقط على بيانات شركة استيراد وتوزيع مصرية. مفيش أداة واحدة هنا
    بتكتب أو بتعدّل أي حاجة — كلها استعلامات.

    القواعد المهمة وانت بتقرا الأرقام:
    - كل المبالغ بالجنيه المصري إلا لو الأداة كتبت العملة صراحةً (فواتير
      الشراء ممكن تكون بالدولار أو اليورو أو الليرة ومعاها سعر الصرف).
    - رصيد التاجر موجب يعني عليه فلوس للشركة.
    - رصيد المخزون = مجموع حركات المخزون، مفيش عمود رصيد مخزّن.
    - التواريخ بصيغة YYYY-MM-DD.
    - الفواتير المؤكّدة بس هي اللي بتدخل الحسابات — المسودات بره كل حاجة.
    - لو المستخدم سأل سؤال مالي، رجّع الرقم زي ما الأداة رجّعته من غير
      ما تحسب تقديرات من عندك.

    أدوات سجل النشاط محكومة بصلاحية المستخدم: المدير بيشوف نشاط أي حد،
    وباقي الأدوار بيشوفوا نشاطهم هما بس.
    TXT)]
class ErpServer extends Server
{
    /*
     * كل الأدوات متاحة هنا. التقسيم على وكلاء بيحصل في لوحة «اسأل
     * السيستم» بس — عميل الـ MCP (كلود) بيشوف المجموعة كاملة ويختار.
     */
    protected array $tools = [
        // المخزون
        LowStockProducts::class,
        StockLookup::class,
        SlowMovingProducts::class,
        FastMovingProducts::class,
        ForecastNeeds::class,

        // المبيعات
        SalesSummary::class,
        SalesBreakdown::class,
        SalesDecline::class,
        TopProducts::class,
        BestCustomers::class,
        RepsComparison::class,
        TraderBalance::class,
        TopDebtors::class,

        // الفواتير
        InvoiceLookup::class,

        // سجل النشاط — محكومة بصلاحية المستخدم جواها
        RecentActivity::class,
        LastLogin::class,
        WhoDid::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
