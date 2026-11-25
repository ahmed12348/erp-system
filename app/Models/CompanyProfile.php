<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * بيانات الشركة اللي بتظهر على كل مستند مطبوع.
 *
 * صف واحد بس في الجدول. `current()` بترجّعه، وبتعمله لو مش موجود باسم
 * من `config('app.name')` — فالنظام الفاضي بيطبع باسم معقول من غير ما
 * حد يفتح الشاشة.
 */
class CompanyProfile extends Model
{
    use RecordsActivity;

    /**
     * اسم السجل في سجل النشاط
     *
     * @var string
     */
    protected $activityLog = 'company';

    /**
     * الحقول اللي بيتسجّل تغيّرها
     *
     * @var array<int, string>
     */
    protected $activityFields = [
        'name', 'address', 'phones', 'tax_number', 'commercial_registry', 'footer_note',
    ];

    protected $fillable = [
        'name', 'logo_path', 'address', 'phones',
        'tax_number', 'commercial_registry', 'footer_note',
    ];

    /**
     * بيانات الشركة الحالية — بتتعمل لو لسه مااتسجّلتش.
     *
     * على نظام فاضي بيعمل صف باسم من `config('app.name')`، فالطباعة
     * تطلع باسم معقول من غير ما حد يفتح شاشة الإعدادات.
     *
     * **من غير كاش ساكن عن قصد.** الكاش الساكن بيعيش أطول من الطلب
     * (وبيعيش بين الاختبارات، وعلى Octane بيعيش بين الطلبات كلها)،
     * فبيرجّع صف اتمسح من الداتابيز والحفظ بيروح على سجل مش موجود.
     * الاستعلام ده بيتنادى كام مرة في الطلب، والمكسب مايستاهلش الخطر.
     */
    public static function current(): self
    {
        return self::query()->first()
            ?? self::create(['name' => (string) config('app.name')]);
    }

    /**
     * رابط اللوجو للعرض على الشاشة.
     *
     * اللوجو على القرص العام عشان الطباعة والـ PDF يقدروا يقروه —
     * mPDF بيقرا الملف من المسار المحلي، والمتصفح بيقراه من الرابط.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }

    /** المسار المحلي للوجو — ده اللي mPDF بيقرا منه */
    public function logoPath(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $path = Storage::disk('public')->path($this->logo_path);

        return is_file($path) ? $path : null;
    }

    /**
     * البيانات بالشكل اللي الواجهة بتستقبله.
     *
     * كل شاشات الطباعة بتاخد `company` — وقبل كده كانت بتاخد نص اسم
     * الشركة بس. الشكل ده بيخلّيها تاخد الترويسة كلها من غير ما كل
     * شاشة تعرف من فين تجيبها.
     *
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return [
            'name' => $this->name,
            // رابط للمتصفح، ومسار محلي للـ PDF — mPDF مابيجيبش من الشبكة
            'logo' => $this->logoUrl(),
            'logoFile' => $this->logoPath(),
            'address' => $this->address,
            'phones' => $this->phones,
            'taxNumber' => $this->tax_number,
            'commercialRegistry' => $this->commercial_registry,
            'footerNote' => $this->footer_note,
        ];
    }
}
