<script setup lang="ts">
import PrintSheet from '@/components/print/PrintSheet.vue';
import ReportTable from '@/components/reports/ReportTable.vue';
import { formatDate } from '@/lib/format';

const props = defineProps<{
    company: string;
    report: string;
    filters: { from: string; to: string; groupBy: string };
    data: Record<string, unknown>;
    printedAt: string;
}>();

const TITLES: Record<string, string> = {
    period: 'تقرير المبيعات على الفترة',
    product: 'تقرير المبيعات حسب الصنف',
    customer: 'تقرير المبيعات حسب التاجر',
    inventory: 'تقرير تقييم المخزون',
};

const subtitle =
    props.report === 'inventory'
        ? 'الأرصدة كما هي بتاريخ الطباعة'
        : `الفترة من ${formatDate(props.filters.from)} إلى ${formatDate(props.filters.to)}`;
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
