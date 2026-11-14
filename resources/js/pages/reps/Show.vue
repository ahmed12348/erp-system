<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { formatDateShort, formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    rep: {
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
    customers: {
        id: number;
        name: string;
        city: string | null;
        balance: number;
    }[];
    invoices: {
        id: number;
        number: string;
        date: string;
        customer: string | null;
        total: number;
        remaining: number;
    }[];
    filters: { from?: string; to?: string };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});
</script>

<template>
    <Head :title="rep.name" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="rep.name"
            :description="`${rep.region ?? ''} · عمولة ${formatNumber(rep.commissionRate, 2)}% على المحصّل`"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">صافي المبيعات</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatMoney(rep.netSales) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatNumber(rep.invoices) }} فاتورة
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">المحصّل</p>
                <p class="mt-2 text-xl font-bold text-status-good tabular-nums">
                    {{ formatMoney(rep.collected) }}
                </p>
                <p
                    v-if="rep.collectionRate !== null"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    نسبة تحصيل {{ formatNumber(rep.collectionRate, 1) }}%
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">المستحق على تجاره</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatMoney(rep.outstanding) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatNumber(rep.customers) }} تاجر
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">العمولة المستحقة</p>
                <p
                    class="mt-2 text-xl font-bold text-chart-1 tabular-nums"
                    data-test="rep-commission"
                >
                    {{ formatMoney(rep.commission) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatNumber(rep.commissionRate, 2) }}% × المحصّل
                </p>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <SectionCard title="تجار المندوب" flush>
                <div class="max-h-96 scrollbar-thin overflow-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="sticky top-0 border-b border-border bg-card text-xs text-muted-foreground"
                        >
                            <tr>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    التاجر
                                </th>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    المحافظة
                                </th>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    الرصيد
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="customer in customers"
                                :key="customer.id"
                                class="transition-colors hover:bg-accent/60"
                            >
                                <td class="px-5 py-3">
                                    <Link
                                        :href="`/customers/${customer.id}`"
                                        class="font-medium hover:text-primary hover:underline"
                                    >
                                        {{ customer.name }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {{ customer.city ?? '—' }}
                                </td>
                                <td
                                    class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                                >
                                    {{ formatMoney(customer.balance) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>

            <SectionCard title="آخر فواتيره" flush>
                <div class="max-h-96 scrollbar-thin overflow-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="sticky top-0 border-b border-border bg-card text-xs text-muted-foreground"
                        >
                            <tr>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    الفاتورة
                                </th>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    التاجر
                                </th>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    التاريخ
                                </th>
                                <th class="px-5 py-2.5 text-start font-medium">
                                    الإجمالي
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="transition-colors hover:bg-accent/60"
                            >
                                <td
                                    class="px-5 py-3 whitespace-nowrap tabular-nums"
                                >
                                    <Link
                                        :href="`/sales/${invoice.id}`"
                                        class="font-medium hover:text-primary hover:underline"
                                    >
                                        {{ invoice.number }}
                                    </Link>
                                </td>
                                <td class="max-w-40 truncate px-5 py-3">
                                    {{ invoice.customer ?? '—' }}
                                </td>
                                <td
                                    class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    {{ formatDateShort(invoice.date) }}
                                </td>
                                <td
                                    class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                                >
                                    {{ formatMoney(invoice.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </div>
    </div>
</template>
