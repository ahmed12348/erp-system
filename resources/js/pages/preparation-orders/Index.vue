<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardList, Plus, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateShort, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    number: string;
    date: string;
    status: string;
    statusLabel: string;
    customer: string | null;
    warehouse: string | null;
    createdBy: string | null;
    itemCount: number;
    invoiceId: number | null;
};

const props = defineProps<{
    orders: { data: Row[] } & PaginatorMeta;
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    statuses: { value: string; label: string }[];
    customers: { id: number; name: string }[];
    warehouses: { id: number; name: string }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    customer_id: props.filters.customer_id ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/preparation-orders',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watchDebounced(() => filters.search, apply, { debounce: 350 });

/** المفتوح والمحضّر لسه حاجزين كميات — بنلوّنهم عشان يبانوا */
const STATUS_TONE: Record<string, string> = {
    open: 'bg-status-warning/10 text-status-warning',
    prepared: 'bg-primary/10 text-primary',
    converted: 'bg-status-good/10 text-status-good',
    cancelled: 'bg-muted text-muted-foreground',
};

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="طلبات التحضير" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="طلبات التحضير"
            :description="`${formatNumber(orders.total)} طلب`"
        >
            <template #actions>
                <Button as-child>
                    <Link href="/preparation-orders/create">
                        <Plus class="size-4" />
                        طلب تحضير جديد
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search
                            class="absolute start-2.5 top-2.5 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="filters.search"
                            class="h-9 w-56 ps-8"
                            placeholder="رقم الطلب أو اسم التاجر"
                            data-test="order-search"
                        />
                    </div>
                    <select
                        v-model="filters.status"
                        :class="SELECT_CLASS"
                        data-test="filter-status"
                        @change="apply"
                    >
                        <option value="">كل الحالات</option>
                        <option
                            v-for="s in statuses"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </option>
                    </select>
                    <select
                        v-model="filters.customer_id"
                        :class="SELECT_CLASS"
                        @change="apply"
                    >
                        <option value="">كل التجار</option>
                        <option
                            v-for="c in customers"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                    <select
                        v-model="filters.warehouse_id"
                        :class="SELECT_CLASS"
                        @change="apply"
                    >
                        <option value="">كل المخازن</option>
                        <option
                            v-for="w in warehouses"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.name }}
                        </option>
                    </select>
                </div>
            </template>

            <EmptyState
                v-if="!hasAny"
                :icon="ClipboardList"
                title="مفيش طلبات تحضير لسه"
                description="الطلب بيروح للمخزن يجهّز عليه بضاعة التاجر — كميات بس من غير أسعار، وبيحجز الكميات لحد ما يتحوّل لفاتورة."
            />

            <EmptyState
                v-else-if="!orders.data.length"
                :icon="Search"
                title="الفلتر مش جايب نتيجة"
                description="غيّر الفلتر أو امسحه عشان تشوف باقي الطلبات."
            />

            <div v-else class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                رقم الطلب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاجر
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                أصناف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الحالة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                فتحه
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in orders.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium whitespace-nowrap">
                                <Link
                                    :href="`/preparation-orders/${row.id}`"
                                    class="tabular-nums hover:underline"
                                    :data-test="`order-${row.number}`"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3">{{ row.customer ?? '—' }}</td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.warehouse ?? '—' }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(row.itemCount) }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-md px-2 py-0.5 text-xs font-medium"
                                    :class="STATUS_TONE[row.status]"
                                >
                                    {{ row.statusLabel }}
                                </span>
                                <Link
                                    v-if="row.invoiceId"
                                    :href="`/sales/${row.invoiceId}`"
                                    class="ms-2 text-xs text-muted-foreground hover:underline"
                                >
                                    الفاتورة
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.createdBy ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="orders.data.length" :meta="orders" />
        </SectionCard>
    </div>
</template>
