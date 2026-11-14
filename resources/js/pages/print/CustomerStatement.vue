<script setup lang="ts">
import PrintSheet from '@/components/print/PrintSheet.vue';
import { formatDate, formatDateShort, formatMoney } from '@/lib/format';

type StatementRow = {
    id: number;
    kind: string;
    date: string;
    reference: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
};

const props = defineProps<{
    company: string;
    customer: {
        name: string;
        phone: string | null;
        city: string | null;
        address: string | null;
    };
    summary: {
        sales: number;
        paid: number;
        balance: number;
        creditLimit: number | null;
    };
    statement: {
        opening: number;
        rows: StatementRow[];
        closing: number;
        totals: { debit: number; credit: number };
    };
    filters: { from: string | null; to: string | null };
    printedAt: string;
}>();

const period = (() => {
    const { from, to } = props.filters;

    if (from && to) {
        return `الفترة من ${formatDate(from)} إلى ${formatDate(to)}`;
    }

    if (from) {
        return `من ${formatDate(from)}`;
    }

    if (to) {
        return `حتى ${formatDate(to)}`;
    }

    return 'كل الحركة حتى تاريخه';
})();
</script>

<template>
    <PrintSheet
        :company="company"
        title="كشف حساب تاجر"
        :subtitle="period"
        :printed-at="printedAt"
    >
        <!-- بيانات التاجر -->
        <section class="mt-6 grid grid-cols-2 gap-6 text-sm">
            <div>
                <p class="mb-1 text-xs font-semibold text-neutral-500">
                    التاجر
                </p>
                <p class="text-base font-bold">{{ customer.name }}</p>
                <p class="mt-0.5 text-neutral-600">
                    {{ customer.city ?? '—' }}
                    <template v-if="customer.address">
                        — {{ customer.address }}</template
                    >
                </p>
                <p v-if="customer.phone" class="mt-0.5 text-neutral-600">
                    تليفون: <span dir="ltr">{{ customer.phone }}</span>
                </p>
            </div>

            <div class="space-y-1 text-sm">
                <div
                    class="flex justify-between border-b border-neutral-200 pb-1"
                >
                    <span class="text-neutral-500">إجمالي المبيعات</span>
                    <span class="font-semibold tabular-nums">
                        {{ formatMoney(summary.sales) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-b border-neutral-200 pb-1"
                >
                    <span class="text-neutral-500">إجمالي التحصيلات</span>
                    <span class="font-semibold tabular-nums">
                        {{ formatMoney(summary.paid) }}
                    </span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="font-semibold">الرصيد المستحق</span>
                    <span class="text-base font-bold tabular-nums">
                        {{ formatMoney(summary.balance) }}
                    </span>
                </div>
            </div>
        </section>

        <!-- الحركة -->
        <table class="mt-7 w-full border-collapse text-[13px]">
            <thead>
                <tr class="bg-neutral-100 text-xs">
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        التاريخ
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        البيان
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        المرجع
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        مدين
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        دائن
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الرصيد
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-neutral-50">
                    <td
                        class="border border-neutral-300 px-2 py-1.5 text-neutral-600"
                        colspan="5"
                    >
                        رصيد ما قبل الفترة
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 font-semibold tabular-nums"
                    >
                        {{ formatMoney(statement.opening) }}
                    </td>
                </tr>

                <tr
                    v-for="row in statement.rows"
                    :key="`${row.kind}-${row.id}`"
                >
                    <td
                        class="border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums"
                    >
                        {{ formatDateShort(row.date) }}
                    </td>
                    <td class="border border-neutral-300 px-2 py-1.5">
                        {{ row.description }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ row.reference }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums"
                    >
                        {{ row.debit > 0 ? formatMoney(row.debit) : '—' }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums"
                    >
                        {{ row.credit > 0 ? formatMoney(row.credit) : '—' }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 font-semibold whitespace-nowrap tabular-nums"
                    >
                        {{ formatMoney(row.balance) }}
                    </td>
                </tr>

                <tr v-if="!statement.rows.length">
                    <td
                        colspan="6"
                        class="border border-neutral-300 px-2 py-6 text-center text-neutral-500"
                    >
                        مفيش حركة في الفترة المحددة
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="bg-neutral-100 font-bold">
                    <td class="border border-neutral-300 px-2 py-2" colspan="3">
                        الإجمالي
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-2 tabular-nums"
                    >
                        {{ formatMoney(statement.totals.debit) }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-2 tabular-nums"
                    >
                        {{ formatMoney(statement.totals.credit) }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-2 tabular-nums"
                    >
                        {{ formatMoney(statement.closing) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- التوقيعات -->
        <section class="mt-12 grid grid-cols-2 gap-10 text-xs text-neutral-600">
            <div class="border-t border-neutral-400 pt-2 text-center">
                توقيع المحاسب
            </div>
            <div class="border-t border-neutral-400 pt-2 text-center">
                توقيع التاجر
            </div>
        </section>
    </PrintSheet>
</template>
