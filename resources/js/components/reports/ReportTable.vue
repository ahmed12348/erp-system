<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft } from '@lucide/vue';
import { ref } from 'vue';
import {
    formatDate,
    formatDateShort,
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

/*
 * السطور المفتوحة في تقرير الفترة.
 *
 * «اليوم ده كان 40 ألف — طب أنهي فواتير؟» سؤال طبيعي، والفواتير بتيجي
 * مع التقرير أصلاً فالفتح مابيعملش أي نداء. الفتح على الشاشة بس:
 * الورقة المطبوعة ما ينفعش حد يدوس عليها.
 */
const expanded = ref<Set<string>>(new Set());

function toggle(period: string) {
    if (props.print) {
        return;
    }

    const next = new Set(expanded.value);

    if (!next.delete(period)) {
        next.add(period);
    }

    expanded.value = next;
}

/** نغمة حالة الفاتورة في تقرير الفواتير */
const STATUS_TONE: Record<string, string> = {
    confirmed: 'text-status-good',
    draft: 'text-muted-foreground',
    cancelled: 'text-status-critical',
};
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
                <template v-for="row in data.rows as any[]" :key="row.period">
                    <tr
                        :class="
                            print
                                ? ''
                                : 'cursor-pointer transition-colors hover:bg-accent/60'
                        "
                        :data-test="`period-${row.period}`"
                        @click="toggle(row.period)"
                    >
                        <td :class="cell">
                            <span class="flex items-center gap-1.5">
                                <component
                                    :is="
                                        expanded.has(row.period)
                                            ? ChevronDown
                                            : ChevronLeft
                                    "
                                    v-if="!print"
                                    class="size-3.5 text-muted-foreground"
                                />
                                {{ periodLabel(row.period) }}
                            </span>
                        </td>
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

                    <!-- فواتير اليوم المفتوح — بأرقامها وإجمالياتها -->
                    <tr
                        v-if="!print && expanded.has(row.period)"
                        :data-test="`period-invoices-${row.period}`"
                    >
                        <td colspan="6" class="bg-muted/30 px-5 py-3">
                            <ul class="space-y-1.5 text-xs">
                                <li
                                    v-for="invoice in row.invoices ?? []"
                                    :key="invoice.id"
                                    class="flex items-center justify-between gap-4"
                                >
                                    <Link
                                        :href="`/sales/${invoice.id}`"
                                        class="font-medium tabular-nums hover:underline"
                                        :data-test="`period-invoice-${invoice.number}`"
                                    >
                                        {{ invoice.number }}
                                    </Link>
                                    <span class="flex items-center gap-4">
                                        <span class="text-muted-foreground">
                                            المحصّل
                                            {{ formatMoney(invoice.paid) }}
                                        </span>
                                        <span
                                            class="font-semibold tabular-nums"
                                        >
                                            {{ formatMoney(invoice.total) }}
                                        </span>
                                    </span>
                                </li>
                            </ul>
                        </td>
                    </tr>
                </template>
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

        <!-- فاتورة في كل سطر — مبيعات أو مشتريات -->
        <template
            v-else-if="
                report === 'sale_invoices' || report === 'purchase_invoices'
            "
        >
            <thead
                :class="
                    print
                        ? ''
                        : 'border-b border-border text-xs text-muted-foreground'
                "
            >
                <tr>
                    <th :class="head">رقم الفاتورة</th>
                    <th :class="head">التاريخ</th>
                    <th :class="head">
                        {{
                            report === 'purchase_invoices' ? 'المورد' : 'التاجر'
                        }}
                    </th>
                    <th :class="head">المندوب</th>
                    <th :class="head">الصافي</th>
                    <th :class="head">الضريبة</th>
                    <th :class="head">الإجمالي</th>
                    <th :class="head">المحصّل</th>
                    <th :class="head">المتبقي</th>
                    <th :class="head">الحالة</th>
                </tr>
            </thead>
            <tbody :class="print ? '' : 'divide-y divide-border'">
                <tr
                    v-for="row in data.rows as any[]"
                    :key="row.id"
                    :class="print ? '' : 'transition-colors hover:bg-accent/60'"
                >
                    <td :class="cell" class="font-medium whitespace-nowrap">
                        <!-- الرقم بيفتح الفاتورة — ده أهم حاجة في التقرير ده -->
                        <Link
                            v-if="!print"
                            :href="row.link"
                            class="tabular-nums hover:underline"
                            :data-test="`report-invoice-${row.number}`"
                        >
                            {{ row.number }}
                        </Link>
                        <span v-else class="tabular-nums">{{
                            row.number
                        }}</span>
                    </td>
                    <td :class="cell" class="whitespace-nowrap tabular-nums">
                        {{ formatDateShort(row.date) }}
                    </td>
                    <td :class="cell">{{ row.party }}</td>
                    <td :class="cell" class="text-muted-foreground">
                        {{ row.rep ?? '—' }}
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
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney(row.remaining) }}
                    </td>
                    <td :class="cell">
                        <span :class="print ? '' : STATUS_TONE[row.status]">
                            {{ row.statusLabel }}
                        </span>
                    </td>
                </tr>
            </tbody>
            <tfoot :class="print ? '' : 'border-t-2 border-border bg-muted/40'">
                <tr class="font-bold">
                    <td :class="cell" colspan="4">
                        الإجمالي ({{ formatNumber((data.totals as any).count) }}
                        فاتورة مؤكّدة)
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
                    <td :class="cell" class="tabular-nums">
                        {{ formatMoney((data.totals as any).remaining) }}
                    </td>
                    <td :class="cell"></td>
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
