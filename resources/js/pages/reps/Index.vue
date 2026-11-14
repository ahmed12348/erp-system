<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { UserRound } from '@lucide/vue';
import { computed, reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Input } from '@/components/ui/input';
import { formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Rep = {
    id: number;
    name: string;
    phone: string | null;
    region: string | null;
    commissionRate: number;
    customers: number;
    invoices: number;
    netSales: number;
    collected: number;
    outstanding: number;
    commission: number;
    collectionRate: number | null;
};

const props = defineProps<{
    reps: Rep[];
    filters: { from?: string; to?: string };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/reps',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

const totals = computed(() => ({
    netSales: props.reps.reduce((s, r) => s + r.netSales, 0),
    collected: props.reps.reduce((s, r) => s + r.collected, 0),
    outstanding: props.reps.reduce((s, r) => s + r.outstanding, 0),
    commission: props.reps.reduce((s, r) => s + r.commission, 0),
}));
</script>

<template>
    <Head title="المندوبين" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="مندوبي المبيعات"
            description="العمولة بتتحسب على المحصّل مش على المبيعات"
        />

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="filters.from"
                        type="date"
                        class="h-9 w-38"
                        aria-label="من تاريخ"
                        data-test="reps-from"
                        @change="apply"
                    />
                    <Input
                        v-model="filters.to"
                        type="date"
                        class="h-9 w-38"
                        aria-label="إلى تاريخ"
                        @change="apply"
                    />
                </div>
            </template>

            <div v-if="reps.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المندوب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المنطقة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التجار
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الفواتير
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                صافي المبيعات
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المحصّل
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                نسبة التحصيل
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المستحق على تجاره
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                العمولة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="rep in reps"
                            :key="rep.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="`/reps/${rep.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ rep.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ rep.region ?? '—' }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(rep.customers) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(rep.invoices) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(rep.netSales) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-status-good tabular-nums"
                            >
                                {{ formatMoney(rep.collected) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                <template v-if="rep.collectionRate !== null">
                                    <span
                                        :class="
                                            rep.collectionRate >= 70
                                                ? 'text-status-good'
                                                : rep.collectionRate >= 45
                                                  ? 'text-status-warning'
                                                  : 'text-status-critical'
                                        "
                                    >
                                        {{
                                            formatNumber(rep.collectionRate, 1)
                                        }}%
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(rep.outstanding) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                <span class="font-semibold">
                                    {{ formatMoney(rep.commission) }}
                                </span>
                                <span
                                    class="ms-1 text-xs text-muted-foreground"
                                >
                                    ({{ formatNumber(rep.commissionRate, 2) }}%)
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40">
                        <tr class="font-bold">
                            <td colspan="4" class="px-5 py-3">الإجمالي</td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(totals.netSales) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(totals.collected) }}
                            </td>
                            <td class="px-5 py-3"></td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                data-test="reps-outstanding"
                            >
                                {{ formatMoney(totals.outstanding) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(totals.commission) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <EmptyState v-else :icon="UserRound" title="مفيش مندوبين مسجّلين" />
        </SectionCard>
    </div>
</template>
