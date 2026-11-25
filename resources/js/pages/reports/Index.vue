<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileChartColumn, Printer, Search, Warehouse } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import DownloadMenu from '@/components/DownloadMenu.vue';
import PageHeader from '@/components/PageHeader.vue';
import ReportTable from '@/components/reports/ReportTable.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    report: string;
    filters: {
        from: string;
        to: string;
        groupBy: string;
        search: string | null;
    };
    data: Record<string, unknown>;
    /** قايمة التقارير جاية من السيرفر — مصدر واحد مع التحميل */
    reports: { value: string; label: string; snapshot: boolean }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const state = reactive({
    report: props.report,
    from: props.filters.from,
    to: props.filters.to,
    groupBy: props.filters.groupBy,
    search: props.filters.search ?? '',
});

/** التقرير الحالي لقطة لحظية؟ وقتها خانات التاريخ مالهاش معنى */
const isSnapshot = computed(
    () =>
        props.reports.find((r) => r.value === state.report)?.snapshot ?? false,
);

/** الفلاتر الفاضية ما بتتحطش في الرابط — يفضل نضيف ومقروء */
function query(): Record<string, string> {
    return Object.fromEntries(
        Object.entries(state).filter(([, value]) => value !== ''),
    ) as Record<string, string>;
}

function apply() {
    router.get('/reports', query(), {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

watchDebounced(() => state.search, apply, { debounce: 350 });

function openPrint() {
    window.open(
        `/reports/print?${new URLSearchParams(query()).toString()}`,
        '_blank',
    );
}

/** نفس فلاتر الشاشة بتروح للتحميل، فالملف بيطابق اللي قدامك */
const exportHref = computed(
    () => `/exports/report?${new URLSearchParams(query()).toString()}`,
);

/** كل التقارير بترجع المفتاح rows — لو فاضي نعرض حالة فاضية بدل جدول بعناوين بس */
const rowCount = computed(() => ((props.data.rows as unknown[]) ?? []).length);

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
                <DownloadMenu :href="exportHref" />
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
                            v-for="r in reports"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
                        </option>
                    </select>

                    <!--
                        البحث برقم الفاتورة — بيشتغل على كل تقرير.
                        مطابقة جزئية، فـ «0001» بتجيب S-2026-00001.
                    -->
                    <div class="relative">
                        <Search
                            class="absolute start-2.5 top-2.5 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="state.search"
                            class="h-9 w-44 ps-8"
                            placeholder="رقم الفاتورة"
                            aria-label="بحث برقم الفاتورة"
                            data-test="report-search"
                        />
                    </div>

                    <template v-if="!isSnapshot">
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

            <div v-if="rowCount > 0" class="scrollbar-thin overflow-x-auto">
                <ReportTable
                    :report="report"
                    :data="data"
                    :group-by="filters.groupBy"
                />
            </div>

            <!--
                البحث أول احتمال نقوله: لو المستخدم كاتب رقم فاتورة
                وماطلعش حاجة، «سجّل أول فاتورة بيع» إجابة غلط على سؤاله.
            -->
            <EmptyState
                v-else-if="state.search"
                large
                :icon="Search"
                title="مفيش نتيجة للرقم ده"
                :description="`مفيش فاتورة رقمها فيه «${state.search}» في التقرير ده. جرّب جزء من الرقم، أو امسح البحث.`"
            />

            <EmptyState
                v-else-if="isSnapshot"
                large
                :icon="Warehouse"
                title="مفيش أصناف في المخزن"
                description="تقييم المخزون بيحسب الكميات بالتكلفة وبسعر البيع — سجّل أول فاتورة شراء الأول."
                :action="{
                    label: 'فاتورة شراء جديدة',
                    href: '/purchases/create',
                }"
            />

            <EmptyState
                v-else-if="report === 'purchase_invoices'"
                large
                :icon="FileChartColumn"
                title="مفيش مشتريات في الفترة دي"
                description="وسّع الفترة أو سجّل أول فاتورة شراء، والتقرير هيتملى لوحده."
                :action="{
                    label: 'فاتورة شراء جديدة',
                    href: '/purchases/create',
                }"
            />

            <EmptyState
                v-else
                large
                :icon="FileChartColumn"
                title="مفيش مبيعات في الفترة دي"
                description="وسّع الفترة أو سجّل أول فاتورة بيع، والتقرير هيتملى لوحده."
                :action="{ label: 'فاتورة بيع جديدة', href: '/sales/create' }"
            />
        </SectionCard>
    </div>
</template>
