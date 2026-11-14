<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Truck } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    country: string | null;
    phone: string | null;
    currency: string;
    purchases: number;
    paid: number;
    balance: number;
    purchasesEgp: number;
    invoiceCount: number;
};

defineProps<{
    suppliers: Row[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});
</script>

<template>
    <Head title="الموردون" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="الموردون"
            description="أرقام كل مورد معروضة بعملته — التحويل للجنيه في عمود منفصل"
        />

        <SectionCard flush>
            <div v-if="suppliers.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المورد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                البلد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                العملة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                عدد الفواتير
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                إجمالي المشتريات
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المدفوع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المستحق
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المشتريات بالجنيه
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in suppliers"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="`/suppliers/${row.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ row.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.country ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ row.currency }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ formatNumber(row.invoiceCount) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{
                                    formatMoney(row.purchases, row.currency, 2)
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-status-good tabular-nums"
                            >
                                {{ formatMoney(row.paid, row.currency, 2) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                                :class="
                                    row.balance > 0
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatMoney(row.balance, row.currency, 2) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatMoney(row.purchasesEgp) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else :icon="Truck" title="مفيش موردين مسجّلين" />
        </SectionCard>
    </div>
</template>
