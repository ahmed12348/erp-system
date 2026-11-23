{{--
    جدول عام — بيخدم التقارير وجداول الوكلاء.

    بياخد `headers` و`rows`، والأعمدة اللي في `numeric` بتتحاذى على
    الشمال. فيه كمان `summary` لسطور الملخّص فوق الجدول.
--}}
@extends('pdf.layout')

@section('content')

    @if (! empty($summary))
        <div class="summary">
            @foreach ($summary as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif

    @if (empty($rows))
        <p class="empty">مفيش بيانات في الفترة دي.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($headers as $index => $header)
                        <th class="{{ in_array($index, $numeric ?? [], true) ? 'num' : '' }}">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="{{ ($row['_total'] ?? false) ? 'total' : '' }}">
                        @foreach (array_values(array_diff_key($row, ['_total' => null])) as $index => $cell)
                            <td class="{{ in_array($index, $numeric ?? [], true) ? 'num' : '' }}">
                                {{ $cell }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection
