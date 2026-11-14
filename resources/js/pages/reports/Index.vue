<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { reactive } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import ReportTable from '@/components/reports/ReportTable.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    report: string;
    filters: { from: string; to: string; groupBy: string };
    data: Record<string, unknown>;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const REPORTS = [
    { key: 'period', label: 'المبيعات على الفترة' },
    { key: 'product', label: 'المبيعات حسب الصنف' },
    { key: 'customer', label: 'المبيعات حسب التاجر' },
    { key: 'inventory', label: 'تقييم المخزون' },
];

const state = reactive({
    report: props.report,
    from: props.filters.from,
    to: props.filters.to,
    groupBy: props.filters.groupBy,
});

function apply() {
    router.get(
        '/reports',
        { ...state },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function openPrint() {
    const params = new URLSearchParams(
        state as unknown as Record<string, string>,
    );

    window.open(`/reports/print?${params.toString()}`, '_blank');
}

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="التقارير" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="التقارير"
            description="تقارير المبيعات والأرباح وتقييم المخزون"
        >
            <template #actions>
                <Button
                    variant="outline"
                    data-test="report-print"
                    @click="openPrint"
                >
                    <Printer class="size-4" />
                    طباعة / حفظ PDF
                </Button>
            </template>
        </PageHeader>

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="state.report"
                        :class="SELECT_CLASS"
                        data-test="report-type"
                        @change="apply"
                    >
                        <option
                            v-for="r in REPORTS"
                            :key="r.key"
                            :value="r.key"
                        >
                            {{ r.label }}
                        </option>
                    </select>

                    <template v-if="state.report !== 'inventory'">
                        <Input
                            v-model="state.from"
                            type="date"
                            class="h-9 w-38"
                            aria-label="من تاريخ"
                            @change="apply"
                        />
                        <Input
                            v-model="state.to"
                            type="date"
                            class="h-9 w-38"
                            aria-label="إلى تاريخ"
                            @change="apply"
                        />
                    </template>

                    <select
                        v-if="state.report === 'period'"
                        v-model="state.groupBy"
                        :class="SELECT_CLASS"
                        data-test="report-group"
                        @change="apply"
                    >
                        <option value="day">باليوم</option>
                        <option value="month">بالشهر</option>
                    </select>
                </div>
            </template>

            <div class="scrollbar-thin overflow-x-auto">
                <ReportTable
                    :report="report"
                    :data="data"
                    :group-by="filters.groupBy"
                />
            </div>
        </SectionCard>
    </div>
</template>
