<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * توليد PDF عربي.
 *
 * مبني على mPDF لأنه المكتبة الوحيدة اللي بتشكّل العربي صح — بتوصل
 * الحروف وتظبّط اتجاه السطر. `dompdf` بيطلّع حروف مقطّعة، وده كان سبب
 * إن النظام فضل فترة بيطبع من المتصفح بس.
 *
 * الخط Tajawal متضمّن في المشروع (`storage/fonts`) مش معتمد على خط
 * موجود على السيرفر — استضافة مشتركة مش مضمون فيها أي خط عربي.
 *
 * **لازم يفضل خط ثابت (static)، مش متغيّر (variable).** قبل كده كان
 * Cairo، وCairo فوق المنبع مابيتوزّعش غير كخط متغيّر — وmPDF
 * مابيعرفش الخطوط المتغيّرة أصلاً: بيحاول يقرا جداول GSUB فيلاقي
 * خاصية من غير lookups ويقع بـ «Undefined array key 0» جوه
 * `TTFontFile::_getGSUBtables()`. يعني كل PDF في النظام كان بيرجّع
 * 500 على سيرفر نضيف. ماكانش باين محليًا لأن mPDF بيخزّن تحليل الخط
 * في `storage/app/mpdf`، والكاش الدافي كان بيعدّي على القراءة كلها.
 *
 * فلو حد فكّر يرجّع Cairo: لازم ينزّل منه نسخة ثابتة الأول
 * (`fonttools varLib.instancer -o Cairo-Regular.ttf Cairo[slnt,wght].ttf
 * wght=400`) ويعمل لها commit — مش ملف `Cairo[slnt,wght].ttf` نفسه.
 * و`ExportTest` بيمسح الكاش قبل ما يرسم، فأي خط متغيّر هيقع فيه.
 *
 * **النص بيفضل نص**: مش صورة. يعني ينفع تحدّده وتنسخه وتدوّر فيه،
 * والأرقام لاتينية زي باقي النظام.
 */
class PdfService
{
    /** مكان الخطوط المتضمّنة */
    private const FONT_DIR = 'fonts';

    /**
     * بيحوّل Blade لـ PDF وبيرجّعه كتحميل.
     *
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename): Response
    {
        $pdf = $this->render($view, $data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            // اسم الملف عربي، فلازم يتكتب بصيغة RFC 5987 عشان المتصفحات تقراه
            'Content-Disposition' => 'attachment; filename="'.$this->asciiName($filename).'.pdf"'
                ."; filename*=UTF-8''".rawurlencode($filename).'.pdf',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data): string
    {
        $mpdf = $this->make($data['title'] ?? config('app.name'));

        $mpdf->WriteHTML(View::make($view, $data)->render());

        return $mpdf->Output('', 'S');
    }

    private function make(string $title): Mpdf
    {
        $fontDir = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 28,
            'margin_bottom' => 18,
            'margin_left' => 12,
            'margin_right' => 12,
            'fontDir' => array_merge($fontDir, [storage_path(self::FONT_DIR)]),
            'fontdata' => $fontData + [
                'tajawal' => [
                    'R' => 'Tajawal-Regular.ttf',
                    // العريض ملف لوحده — من غيره mPDF بيزنق الحروف بنفسه
                    // وبيخرّب وصل العربي في العناوين
                    'B' => 'Tajawal-Bold.ttf',
                    // تشكيل عربي كامل — وصل الحروف والتشكيل
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => 'tajawal',
            'directionality' => 'rtl',
            // القرص الخاص — مش public
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetAuthor(config('app.name'));
        $mpdf->SetCreator(config('app.name'));

        /*
         * بيعلّم كل مقطع عربي بلغته عشان تشكيل OTL يشتغل بنظام اللغة
         * العربي مش بالافتراضي.
         */
        $mpdf->autoScriptToLang = true;

        /*
         * **مقفول عن قصد.** لما بيبقى مفتوح، mPDF بيقرا `lang="ar"` من
         * القالب وبيستبدل خطنا بخطه هو (XB Riyaz) — يعني الخط اللي
         * إحنا ضامّينه للمشروع ما بيتستعملش أصلاً، وكل الـ PDF بتطلع
         * بخط تاني من غير ما حد ياخد باله. القالب كله عربي وإحنا
         * محدّدين الخط بإيدنا، فمفيش حاجة تتخمّن هنا.
         */
        $mpdf->autoLangToFont = false;

        return $mpdf;
    }

    /**
     * اسم احتياطي بحروف لاتينية.
     *
     * المتصفحات القديمة بتقرا `filename` العادي بس، واسم عربي فيه
     * بيطلّع رموز مكسّرة — فبنديها اسم بسيط وبنسيب العربي في
     * `filename*`.
     */
    private function asciiName(string $name): string
    {
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? '';

        return trim($ascii, '-') ?: 'document';
    }
}
