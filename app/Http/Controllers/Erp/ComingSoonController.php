<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * شاشة مؤقتة للأقسام اللي لسه تحت التنفيذ — بديل عن صفحة 404.
 * كل قسم بيتشال من هنا أول ما شاشته الحقيقية تجهز.
 */
class ComingSoonController extends Controller
{
    /** اسم المسار => [العنوان, الوصف] */
    private const SECTIONS = [
        'erp.dashboard' => ['لوحة التحكم', 'نظرة سريعة على المبيعات والمخزون ومديونيات التجار.'],
        'sales.index' => ['المبيعات', 'فواتير البيع للتجار، المرتجعات، وربطها بمنظومة الفاتورة الإلكترونية.'],
        'purchases.index' => ['المشتريات', 'فواتير الاستيراد من الموردين بعملات مختلفة وسعر الصرف.'],
        'inventory.index' => ['المخزون', 'أرصدة الأصناف في المخازن وحركة الوارد والمنصرف.'],
        'customers.index' => ['التجار', 'بيانات التجار، حدود المديونية، وكشف حساب لكل تاجر.'],
        'suppliers.index' => ['الموردون', 'بيانات الموردين والمستحق لكل مورد بعملته.'],
        'treasury.index' => ['الخزينة', 'التحصيلات من التجار والمدفوعات للموردين.'],
        'reports.index' => ['التقارير', 'تقارير المبيعات والأرباح وحركة المخزون.'],
    ];

    public function __invoke(Request $request): Response
    {
        $name = $request->route()?->getName() ?? '';

        [$title, $description] = self::SECTIONS[$name] ?? ['قريبًا', 'هذه الشاشة تحت التنفيذ.'];

        $breadcrumbs = [['title' => 'لوحة التحكم', 'href' => route('erp.dashboard')]];

        // لوحة التحكم نفسها مالهاش أب — مش هنكررها في المسار
        if ($name !== 'erp.dashboard') {
            $breadcrumbs[] = ['title' => $title, 'href' => $request->path()];
        }

        return Inertia::render('ComingSoon', [
            'title' => $title,
            'description' => $description,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
