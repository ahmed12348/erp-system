{{--
    قالب الإيميل العربي.

    مكتوب بجداول و inline styles عن قصد — عملاء الإيميل (أوتلوك
    بالذات) بيتجاهلوا نص الـ CSS الحديث، والجداول هي اللي بتتعرض
    بنفس الشكل في كل مكان.

    `dir="rtl"` على كل عنصر مش على الـ body بس، لأن جيميل بيشيل
    وسم الـ html وبيسيب الجوه بس.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body dir="rtl" style="margin:0; padding:0; background-color:#f4f5f7; font-family:'Segoe UI',Tahoma,Arial,sans-serif;">

<table dir="rtl" role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:24px 12px;">
    <tr>
        <td align="center">

            <table dir="rtl" role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e3e5e8;">

                {{-- الترويسة --}}
                <tr>
                    <td dir="rtl" style="background-color:#0f766e; padding:18px 24px; text-align:right;">
                        <span style="color:#ffffff; font-size:17px; font-weight:bold;">
                            {{ config('app.name') }}
                        </span>
                    </td>
                </tr>

                {{-- المحتوى --}}
                <tr>
                    <td dir="rtl" style="padding:24px; text-align:right; color:#1f2933; font-size:15px; line-height:1.9;">

                        @if (! empty($greeting))
                            <p style="margin:0 0 14px; font-weight:bold;">{{ $greeting }}</p>
                        @endif

                        <p style="margin:0 0 14px; font-size:16px; font-weight:bold; color:#0f766e;">
                            {{ $title }}
                        </p>

                        @foreach ($lines as $line)
                            <p style="margin:0 0 10px;">{{ $line }}</p>
                        @endforeach

                        @if (! empty($actionUrl))
                            <table dir="rtl" role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px;">
                                <tr>
                                    <td style="background-color:#0f766e; border-radius:6px;">
                                        <a href="{{ $actionUrl }}"
                                           style="display:inline-block; padding:10px 22px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">
                                            {{ $actionText ?? 'افتح النظام' }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endif

                    </td>
                </tr>

                {{-- الذيل --}}
                <tr>
                    <td dir="rtl" style="padding:14px 24px; background-color:#fafbfc; border-top:1px solid #e3e5e8; text-align:right; color:#6b7280; font-size:12px; line-height:1.8;">
                        الرسالة دي جاية من {{ config('app.name') }} تلقائيًا.
                        تقدر تقفل التنبيهات دي من «إعدادات التنبيهات» جوه النظام.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
