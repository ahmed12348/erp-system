{{--
    ورقة تحضير A4.

    **مفيش أي عمود سعر هنا، ومتعمّد.** الورقة دي بتتطبع وبتروح لعامل
    المخزن، وبتتبعت على الواتساب للسواق — ومحدش فيهم محتاج يعرف التاجر
    بيشتري بكام.

    خانة «تم» بتتطبع فاضية عشان اللي بيجمّع يعلّم فيها بالقلم وهو ماشي
    على الرفوف، والتوقيعين تحت عشان الورقة تبقى مستند تسليم.
--}}
@extends('pdf.layout')

@section('content')

    {{-- بيانات الطلب --}}
    <table style="margin-top: 4pt;">
        <tr>
            <td style="border: none; padding: 2pt 0; width: 25%;">
                <strong>رقم الطلب:</strong> {{ $order['number'] }}
            </td>
            <td style="border: none; padding: 2pt 0; width: 25%;">
                <strong>التاريخ:</strong> {{ $order['date'] }}
            </td>
            <td style="border: none; padding: 2pt 0; width: 25%;">
                <strong>المخزن:</strong> {{ $order['warehouse'] ?? '—' }}
            </td>
            <td style="border: none; padding: 2pt 0; width: 25%;">
                <strong>الحالة:</strong> {{ $order['statusLabel'] }}
            </td>
        </tr>
        <tr>
            <td style="border: none; padding: 2pt 0;" colspan="2">
                <strong>التاجر:</strong> {{ $order['customer']['name'] ?? '—' }}
            </td>
            <td style="border: none; padding: 2pt 0;" colspan="2">
                @if (! empty($order['customer']['phone']))
                    <strong>تليفون:</strong> <span dir="ltr">{{ $order['customer']['phone'] }}</span>
                @endif
            </td>
        </tr>
        @if (! empty($order['customer']['address']) || ! empty($order['customer']['city']))
            <tr>
                <td style="border: none; padding: 2pt 0;" colspan="4">
                    <strong>العنوان:</strong>
                    {{ trim(($order['customer']['city'] ?? '').' '.($order['customer']['address'] ?? '')) }}
                </td>
            </tr>
        @endif
    </table>

    {{-- البنود --}}
    <table style="margin-top: 10pt;">
        <thead>
            <tr>
                <th style="width: 7%;" class="num">م</th>
                <th style="width: 15%;">الكود</th>
                <th>الصنف</th>
                <th style="width: 12%;">الوحدة</th>
                <th style="width: 14%;" class="num">الكمية</th>
                {{-- بتتطبع فاضية عن قصد — مكان علامة القلم --}}
                <th style="width: 10%;" class="num">تم</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order['items'] as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item['sku'] ?? '—' }}</td>
                    <td>{{ $item['name'] ?? '—' }}</td>
                    <td>{{ $item['unit'] ?? '—' }}</td>
                    <td class="num">
                        {{ rtrim(rtrim(number_format($item['preparedQuantity'] ?? $item['quantity'], 3, '.', ','), '0'), '.') }}
                    </td>
                    {{-- مربع فاضي، مش خانة فاضية: لازم يبان إنه مكان علامة --}}
                    <td class="num" style="height: 18pt;">
                        <span style="border: 0.8pt solid #495057; padding: 0 5pt;">&nbsp;&nbsp;&nbsp;</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (! empty($order['notes']))
        <p style="margin-top: 10pt; font-size: 9pt;">
            <strong>ملاحظات:</strong> {{ $order['notes'] }}
        </p>
    @endif

    {{-- التوقيعات --}}
    <table style="margin-top: 26pt;">
        <tr>
            <td style="border: none; width: 50%; padding: 0 0 26pt;">
                <strong>حضّر البضاعة:</strong> ..........................................
            </td>
            <td style="border: none; width: 50%; padding: 0 0 26pt;">
                <strong>استلم:</strong> ..........................................
            </td>
        </tr>
        <tr>
            <td style="border: none; padding: 0;">
                التوقيع: ..........................................
            </td>
            <td style="border: none; padding: 0;">
                التوقيع: ..........................................
            </td>
        </tr>
    </table>

@endsection
