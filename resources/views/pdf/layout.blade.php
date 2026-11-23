{{--
    قالب الـ PDF الموحّد.

    الهيدر والفوتر بيتحطّوا بوسوم mPDF (`htmlpageheader` و
    `htmlpagefooter`) عشان يتكرّروا على كل صفحة لوحدهم — ده اللي
    بيخلّي ترقيم الصفح شغّال في تقرير من عشر صفحات.

    الأرقام كلها لاتينية والتواريخ عربية، زي باقي النظام بالظبط.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: cairo, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .company {
            font-size: 13pt;
            font-weight: 700;
        }

        .doc-title {
            font-size: 11pt;
            color: #444;
        }

        .meta {
            font-size: 8.5pt;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            background: #f1f3f5;
            border-bottom: 1.2pt solid #adb5bd;
            padding: 5pt 6pt;
            text-align: right;
            font-weight: 700;
            font-size: 9pt;
        }

        td {
            border-bottom: 0.4pt solid #dee2e6;
            padding: 4.5pt 6pt;
            text-align: right;
        }

        /* الأرقام بتتقرا أحسن لما تبقى متحاذية على الشمال */
        td.num, th.num {
            text-align: left;
            direction: ltr;
        }

        tr.total td {
            font-weight: 700;
            background: #f8f9fa;
            border-top: 1pt solid #adb5bd;
        }

        .summary {
            margin-top: 10px;
            font-size: 9.5pt;
        }

        .summary span {
            display: inline-block;
            margin-left: 18px;
        }

        .empty {
            padding: 20pt;
            text-align: center;
            color: #868e96;
        }
    </style>
</head>
<body>

{{-- الهيدر: اسم الشركة واسم المستند والفترة --}}
<htmlpageheader name="page-header">
    <table style="border: none; margin: 0;">
        <tr>
            <td style="border: none; padding: 0;">
                <div class="company">{{ $company }}</div>
                <div class="doc-title">{{ $title }}</div>
            </td>
            <td style="border: none; padding: 0; text-align: left;" class="num">
                <div class="meta">
                    @if (! empty($range))
                        {{ $range }}<br>
                    @endif
                    طُبع في {{ $printedAt }}
                </div>
            </td>
        </tr>
    </table>
    <hr style="border: none; border-bottom: 1pt solid #adb5bd; margin: 4pt 0 0;">
</htmlpageheader>

{{-- الفوتر: رقم الصفحة من إجمالي الصفحات --}}
<htmlpagefooter name="page-footer">
    <hr style="border: none; border-bottom: 0.4pt solid #dee2e6; margin: 0 0 3pt;">
    <table style="border: none; margin: 0;">
        <tr>
            <td style="border: none; padding: 0;" class="meta">{{ $company }}</td>
            <td style="border: none; padding: 0; text-align: left;" class="meta num">
                صفحة {PAGENO} من {nbpg}
            </td>
        </tr>
    </table>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
<sethtmlpagefooter name="page-footer" value="on">

@yield('content')

</body>
</html>
