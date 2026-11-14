<script setup lang="ts">
import {
    formatDate,
    formatMoney,
    formatNumber,
    formatQuantity,
} from '@/lib/format';

/**
 * جدول التقرير — نفس المكوّن بيتستخدم في الشاشة وفي صفحة الطباعة
 * عشان الأرقام اللي بتتطبع تبقى هي نفسها اللي على الشاشة بالظبط.
 */
const props = defineProps<{
    report: string;
    data: Record<string, unknown>;
    groupBy?: string;
    /** نسخة مضغوطة بحدود للطباعة */
    print?: boolean;
}>();

const cell = props.print
    ? 'border border-neutral-300 px-2 py-1.5'
    : 'px-5 py-3';
const head = props.print
    ? 'border border-neutral-300 px-2 py-2 text-start font-semibold bg-neutral-100'
    : 'px-5 py-2.5 text-start font-medium';

function periodLabel(value: string): string {
    // "2026-11" شهري، "2026-11-14" يومي
    return value.length === 7 ? `${value}` : formatDate(value);
}
</script>

<template>
    <table
        class="w-full text-sm"
        :class="print ? 'border-collapse text-[13px]' : ''"
    >
        <!-- مبيعات على فترة -->
        <template v-if="report === 'period'">
            <thead
                :class="
                    print
                        ? ''
                        : 'border-b border-border text-xs text-muted-foreground'
                "
            >
                <tr>
                    <th :class="head">الفترة</th>
                    <th :class="head">عدد الفواتير</th>
                    <th :class="head">صافي المبيعات</th>
                    <th :class="head">الضريبة</th>
                    <th :class="head">الإجمالي</th>
                    <th :class="head">المحصّل</th>
                </tr>
            </thead>
            <tbody :class="print ? '' : 'divide-y divide-border'">
                <tr v-for="row in data.rows as any[]" :key="row.period">
                    <td :class="cell">{{ periodLabel(row.period) }}</td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatNumber(row.count) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.net) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.tax) }}
                    </td>
                    <td :class="cell" class="font-semibold tabular-nums">
                        {{ formatMoney(row.total) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.paid) }}
                    </td>
                </tr>
            </tbody>
            <tfoot :class="print ? '' : 'border-t-2 border-border bg-muted/40'">
                <tr class="font-bold">
                    <td :class="cell">الإجمالي</td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatNumber((data.totals as any).count) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).net) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).tax) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).total) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).paid) }}
                    </td>
                </tr>
            </tfoot>
        </template>

        <!-- مبيعات حسب الصنف -->
        <template v-else-if="report === 'product'">
            <thead
                :class="
                    print
                        ? ''
                        : 'border-b border-border text-xs text-muted-foreground'
                "
            >
                <tr>
                    <th :class="head">الصنف</th>
                    <th :class="head">التصنيف</th>
                    <th :class="head">الكمية</th>
                    <th :class="head">المبيعات</th>
                    <th :class="head">التكلفة</th>
                    <th :class="head">الربح</th>
                    <th :class="head">هامش الربح</th>
                </tr>
            </thead>
            <tbody :class="print ? '' : 'divide-y divide-border'">
                <tr v-for="row in data.rows as any[]" :key="row.id">
                    <td :class="cell" class="font-medium">{{ row.name }}</td>
                    <td :class="cell" class="text-muted-foreground">
                        {{ row.category ?? '—' }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatQuantity(row.quantity, row.unit) }}
                    </td>
                    <td :class="cell" class="font-semibold tabular-nums">
                        {{ formatMoney(row.revenue) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.cost) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.profit) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatNumber(row.margin, 1) }}%
                    </td>
                </tr>
            </tbody>
        </template>

        <!-- مبيعات حسب التاجر -->
        <template v-else-if="report === 'customer'">
            <thead
                :class="
                    print
                        ? ''
                        : 'border-b border-border text-xs text-muted-foreground'
                "
            >
                <tr>
                    <th :class="head">التاجر</th>
                    <th :class="head">المحافظة</th>
                    <th :class="head">عدد الفواتير</th>
                    <th :class="head">المبيعات</th>
                    <th :class="head">المحصّل</th>
                    <th :class="head">المتبقي</th>
                </tr>
            </thead>
            <tbody :class="print ? '' : 'divide-y divide-border'">
                <tr v-for="row in data.rows as any[]" :key="row.id">
                    <td :class="cell" class="font-medium">{{ row.name }}</td>
                    <td :class="cell" class="text-muted-foreground">
                        {{ row.city ?? '—' }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatNumber(row.invoices) }}
                    </td>
                    <td :class="cell" class="font-semibold tabular-nums">
                        {{ formatMoney(row.total) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.paid) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.outstanding) }}
                    </td>
                </tr>
            </tbody>
        </template>

        <!-- تقييم المخزون -->
        <template v-else>
            <thead
                :class="
                    print
                        ? ''
                        : 'border-b border-border text-xs text-muted-foreground'
                "
            >
                <tr>
                    <th :class="head">الصنف</th>
                    <th :class="head">التصنيف</th>
                    <th :class="head">الرصيد</th>
                    <th :class="head">التكلفة</th>
                    <th :class="head">القيمة بالتكلفة</th>
                    <th :class="head">القيمة بسعر البيع</th>
                </tr>
            </thead>
            <tbody :class="print ? '' : 'divide-y divide-border'">
                <tr v-for="row in data.rows as any[]" :key="row.id">
                    <td :class="cell" class="font-medium">{{ row.name }}</td>
                    <td :class="cell" class="text-muted-foreground">
                        {{ row.category ?? '—' }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatQuantity(row.total, row.unit) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.cost) }}
                    </td>
                    <td :class="cell" class="font-semibold tabular-nums">
                        {{ formatMoney(row.value) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.retailValue) }}
                    </td>
                </tr>
            </tbody>
            <tfoot :class="print ? '' : 'border-t-2 border-border bg-muted/40'">
                <tr class="font-bold">
                    <td :class="cell" colspan="4">
                        الإجمالي ({{ formatNumber((data.totals as any).items) }}
                        صنف)
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).cost) }}
                    </td>
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).retail) }}
                    </td>
                </tr>
            </tfoot>
        </template>
    </table>
</template>
