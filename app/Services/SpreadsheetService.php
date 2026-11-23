<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * تصدير الجداول لإكسيل.
 *
 * بيكتب ملف `.xlsx` حقيقي بـ `ZipArchive` اللي جوه PHP — من غير أي
 * مكتبة زيادة. ملف الإكسيل في الآخر مجرد زِب فيه كام ملف XML، وكتابته
 * بإيدنا أرخص بكتير من إننا نضيف PhpSpreadsheet (ميجابايتات ومتطلبات
 * إضافية) على استضافة مشتركة عشان جدول من عشر أعمدة.
 *
 * لو `ZipArchive` مش موجودة لأي سبب، بيرجع CSV بـ BOM — إكسيل
 * بيفتحه عربي صح بفضل الـ BOM، من غيره بيطلّع رموز مكسّرة.
 */
class SpreadsheetService
{
    public function available(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|float|int|null>>  $rows
     */
    public function download(string $filename, array $headers, array $rows): Response
    {
        return $this->available()
            ? $this->xlsx($filename, $headers, $rows)
            : $this->csv($filename, $headers, $rows);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|float|int|null>>  $rows
     */
    private function xlsx(string $filename, array $headers, array $rows): Response
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($headers, $rows));

        $zip->close();

        $contents = (string) file_get_contents($path);

        unlink($path);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => $this->disposition($filename, 'xlsx'),
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|float|int|null>>  $rows
     */
    private function csv(string $filename, array $headers, array $rows): Response
    {
        $handle = fopen('php://temp', 'r+');

        // BOM — من غيره إكسيل بيقرا العربي رموز
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = (string) stream_get_contents($handle);
        fclose($handle);

        return response($contents, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => $this->disposition($filename, 'csv'),
        ]);
    }

    /**
     * ورقة العمل.
     *
     * الأرقام بتتكتب كأرقام (`t` مش مكتوب) عشان إكسيل يجمعها، والنص
     * بيتكتب inline (`t="inlineStr"`) عشان نتجنّب جدول النصوص المشترك
     * — أبسط، وفرق الحجم مالوش معنى في جدول تقرير.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|float|int|null>>  $rows
     */
    private function sheet(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // العرض من اليمين للشمال
            .'<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews>'
            .'<sheetData>';

        $xml .= $this->row(1, $headers, header: true);

        foreach ($rows as $index => $row) {
            $xml .= $this->row($index + 2, array_values($row));
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  array<int, string|float|int|null>  $cells
     */
    private function row(int $number, array $cells, bool $header = false): string
    {
        $xml = '<row r="'.$number.'">';

        foreach ($cells as $index => $value) {
            $ref = $this->column($index).$number;
            $style = $header ? ' s="1"' : '';

            if ($value === null || $value === '') {
                $xml .= '<c r="'.$ref.'"'.$style.'/>';

                continue;
            }

            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="'.$ref.'"'.$style.'><v>'.$value.'</v></c>';

                continue;
            }

            $xml .= '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'
                .htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                .'</t></is></c>';
        }

        return $xml.'</row>';
    }

    /** 0 → A، 25 → Z، 26 → AA */
    private function column(int $index): string
    {
        $name = '';

        for ($i = $index; $i >= 0; $i = intdiv($i, 26) - 1) {
            $name = chr(65 + $i % 26).$name;
        }

        return $name;
    }

    private function disposition(string $filename, string $extension): string
    {
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? '';
        $ascii = trim($ascii, '-') ?: 'export';

        return 'attachment; filename="'.$ascii.'.'.$extension.'"'
            ."; filename*=UTF-8''".rawurlencode($filename).'.'.$extension;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="التقرير" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /** نمطين بس: عادي، وعنوان غامق */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }
}
