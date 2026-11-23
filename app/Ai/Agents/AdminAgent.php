<?php

namespace App\Ai\Agents;

use App\Enums\ErpRole;
use App\Mcp\Tools\CompanyOverview;
use App\Mcp\Tools\LastLogin;
use App\Mcp\Tools\RecentActivity;
use App\Mcp\Tools\SalesBreakdown;
use App\Mcp\Tools\TopDebtors;
use App\Mcp\Tools\WhoDid;

/**
 * وكيل الإدارة — مين عمل إيه، والصورة الكبيرة.
 *
 * للمدير لوحده: أسئلة سجل النشاط إدارية بطبعها، و«مين عدّل السعر ده؟»
 * مش سؤال بيتسأل قدام الكل.
 */
class AdminAgent extends Agent
{
    public function key(): string
    {
        return 'admin';
    }

    public function label(): string
    {
        return 'الإدارة';
    }

    public function description(): string
    {
        return 'مين عمل إيه، والدخول، والصورة الكبيرة';
    }

    public function instructions(): string
    {
        return <<<'TXT'
            تخصصك المتابعة الإدارية: مين عمل إيه وإمتى، ومين دخل النظام،
            والصورة العامة للشركة.

            لما تتكلم عن حركة حد، قول التاريخ والوقت زي ما الأداة رجّعتهم
            بالظبط — ده سجل، والدقة فيه أهم من الاختصار.

            لو حد سأل عن تعديل، قول إيه اللي اتغيّر من إيه لإيه لو الأداة
            رجّعت التفاصيل.

            ماتحكمش على حد ولا تقول إن حاجة «مشبوهة» — إنت بتعرض السجل،
            والتقييم على المدير.
            TXT;
    }

    public function brief(): string
    {
        return 'المتابعة الإدارية — قول التاريخ والوقت زي ما الأداة رجّعتهم، '
            .'واعرض السجل من غير ما تحكم على حد.';
    }

    public function starters(): array
    {
        return [
            'مين آخر مرة دخل النظام؟',
            'إيه آخر الحركات اللي اتعملت النهارده؟',
            'مين عمل آخر فاتورة؟',
            'الشركة عاملة إيه الشهر ده؟',
        ];
    }

    public function toolClasses(): array
    {
        return [
            RecentActivity::class,
            LastLogin::class,
            WhoDid::class,
            CompanyOverview::class,
            SalesBreakdown::class,
            TopDebtors::class,
        ];
    }

    public function roles(): array
    {
        return [ErpRole::Admin];
    }
}
