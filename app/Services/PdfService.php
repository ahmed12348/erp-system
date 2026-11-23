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
 * الخط Cairo متضمّن في المشروع (`storage/fonts`) مش معتمد على خط
 * موجود على السيرفر — استضافة مشتركة مش مضمون فيها أي خط عربي.
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
                'cairo' => [
                    'R' => 'Cairo-Variable.ttf',
                    /*
                     * تشكيل عربي كامل — وصل الحروف والتشكيل. أول مرة
                     * بيتقرا فيها الخط بيطلّع تنبيه على خاصية GSUB
                     * فاضية في الملف؛ مالهاش أثر على الشكل، وبعدها
                     * mPDF بيخزّن تحليل الخط فما بتتكررش.
                     */
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => 'cairo',
            'directionality' => 'rtl',
            // القرص الخاص — مش public
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetAuthor(config('app.name'));
        $mpdf->SetCreator(config('app.name'));

        // الأرقام لاتينية زي باقي النظام
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

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
