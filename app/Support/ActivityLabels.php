<?php

namespace App\Support;

/**
 * الأسماء العربية لسجل النشاط.
 *
 * مكان واحد للترجمة عشان صفحة السجل وأدوات المساعد يقولوا نفس الكلام.
 * لو الاسم مش معروف بنرجّع المفتاح زي ما هو بدل ما نرمي استثناء —
 * السجل بيتقرا، وسطر واحد بصياغة غريبة أهون من صفحة واقعة.
 */
class ActivityLabels
{
    /** أنواع السجلات — نفس قيمة `log_name` */
    private const LOGS = [
        'auth' => 'الدخول',
        'invoice' => 'فاتورة',
        'payment' => 'سند',
        'product' => 'صنف',
        'customer' => 'تاجر',
        'supplier' => 'مورد',
        'warehouse' => 'مخزن',
        'user' => 'مستخدم',
        'category' => 'تصنيف',
        'rep' => 'مندوب',
        'preparation_order' => 'طلب تحضير',
    ];

    /** الأفعال — نفس قيمة `event` */
    private const EVENTS = [
        'created' => 'أضاف',
        'updated' => 'عدّل',
        'deleted' => 'حذف',
        'confirmed' => 'أكّد',
        'prepared' => 'حضّر',
        'cancelled' => 'ألغى',
        'converted' => 'حوّل لفاتورة',
        'eta_sent' => 'أرسل للمنظومة',
        'login' => 'سجّل دخول',
        'login_failed' => 'محاولة دخول فاشلة',
        'logout' => 'سجّل خروج',
    ];

    /**
     * أسماء الحقول — بتظهر في «قبل/بعد» على شاشة السجل.
     */
    private const FIELDS = [
        'name' => 'الاسم',
        'sku' => 'الكود',
        'number' => 'رقم الفاتورة',
        'unit' => 'الوحدة',
        'phone' => 'التليفون',
        'city' => 'المدينة',
        'country' => 'البلد',
        'location' => 'الموقع',
        'currency' => 'العملة',
        'exchange_rate' => 'سعر الصرف',
        'price_tier' => 'شريحة السعر',
        'credit_limit' => 'حد الائتمان',
        'purchase_price' => 'سعر الشراء',
        'sale_price' => 'سعر البيع',
        'wholesale_price' => 'سعر الجملة',
        'min_stock' => 'حد الطلب',
        'opening_balance' => 'الرصيد الافتتاحي',
        'is_active' => 'مفعّل',
        'status' => 'الحالة',
        'type' => 'النوع',
        'total' => 'الإجمالي',
        'paid' => 'المدفوع',
        'amount' => 'المبلغ',
        'method' => 'طريقة الدفع',
        'date' => 'التاريخ',
        'discount' => 'الخصم',
        'email' => 'الإيميل',
        'notes' => 'ملاحظات',
        'reference' => 'المرجع',
        'customer_id' => 'التاجر',
        'supplier_id' => 'المورد',
        'invoice_id' => 'الفاتورة',
        'warehouse_id' => 'المخزن',
        'category_id' => 'التصنيف',
        'sales_rep_id' => 'المندوب',
    ];

    /** قيم معروفة بتتعرض بالعربي بدل الإنجليزي */
    private const VALUES = [
        'draft' => 'مسودة',
        'confirmed' => 'مؤكّدة',
        'cancelled' => 'ملغية',
        // حالات طلب التحضير
        'open' => 'مفتوح',
        'prepared' => 'تم التحضير',
        'converted' => 'تم التحويل لفاتورة',
        'sale' => 'بيع',
        'purchase' => 'شراء',
        'sale_return' => 'مرتجع بيع',
        'purchase_return' => 'مرتجع شراء',
        'receipt' => 'تحصيل',
        'disbursement' => 'صرف',
        'wholesale' => 'جملة الجملة',
        'cash' => 'نقدي',
        'bank' => 'تحويل بنكي',
        'check' => 'شيك',
        'true' => 'نعم',
        'false' => 'لأ',
    ];

    public static function log(?string $name): string
    {
        return self::LOGS[$name] ?? (string) $name;
    }

    public static function event(?string $event): string
    {
        return self::EVENTS[$event] ?? (string) $event;
    }

    public static function field(string $field): string
    {
        return self::FIELDS[$field] ?? $field;
    }

    /** بيحوّل القيمة لنص مقروء — بولياني، تاريخ، أو قيمة معروفة */
    public static function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'نعم' : 'لأ';
        }

        $text = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return self::VALUES[$text] ?? (string) $text;
    }

    /**
     * قايمة الأنواع للفلتر على الشاشة.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function logOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(self::LOGS),
            array_values(self::LOGS),
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function eventOptions(): array
    {
        return array_map(
            fn (string $value, string $label): array => ['value' => $value, 'label' => $label],
            array_keys(self::EVENTS),
            array_values(self::EVENTS),
        );
    }
}
