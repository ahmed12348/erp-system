<script setup lang="ts">
import PrintSheet from '@/components/print/PrintSheet.vue';
import ReportTable from '@/components/reports/ReportTable.vue';
import { formatDate } from '@/lib/format';

const props = defineProps<{
    company: string;
    report: string;
    filters: {
        from: string;
        to: string;
        groupBy: string;
        search: string | null;
    };
    data: Record<string, unknown>;
    printedAt: string;
}>();

const TITLES: Record<string, string> = {
    period: 'تقرير المبيعات على الفترة',
    sale_invoices: 'تقرير المبيعات بالفاتورة',
    purchase_invoices: 'تقرير المشتريات بالفاتورة',
    product: 'تقرير المبيعات حسب الصنف',
    customer: 'تقرير المبيعات حسب التاجر',
    inventory: 'تقرير تقييم المخزون',
};

/*
 * الورقة لازم تقول إنها مفلترة.
 *
 * من غير كده حد بيستلم تقرير فيه فاتورة واحدة بيحسبه كل مبيعات الفترة.
 */
const subtitle = (() => {
    const period =
        props.report === 'inventory'
            ? 'الأرصدة كما هي بتاريخ الطباعة'
            : `الفترة من ${formatDate(props.filters.from)} إلى ${formatDate(props.filters.to)}`;

    return props.filters.search
        ? `${period} · رقم الفاتورة: ${props.filters.search}`
        : period;
})();
</script>

<template>
    <PrintSheet
        :company="company"
        :title="TITLES[report] ?? 'تقرير'"
        :subtitle="subtitle"
        :printed-at="printedAt"
    >
        <div class="mt-7">
            <ReportTable :report="report" :data="data" print />
        </div>
    </PrintSheet>
</template>
