<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\InvoiceLookup;
use App\Mcp\Tools\LastLogin;
use App\Mcp\Tools\LowStockProducts;
use App\Mcp\Tools\RecentActivity;
use App\Mcp\Tools\SalesSummary;
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
    - لو المستخدم سأل سؤال مالي، رجّع الرقم زي ما الأداة رجّعته من غير
      ما تحسب تقديرات من عندك.
    TXT)]
class ErpServer extends Server
{
    protected array $tools = [
        LowStockProducts::class,
        TraderBalance::class,
        TopDebtors::class,
        SalesSummary::class,
        TopProducts::class,
        StockLookup::class,
        InvoiceLookup::class,
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
