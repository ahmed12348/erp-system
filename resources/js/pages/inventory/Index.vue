<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Package, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Input } from '@/components/ui/input';
import { formatMoney, formatNumber, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    category: string | null;
    minStock: number;
    cost: number;
    total: number;
    value: number;
    isLow: boolean;
    warehouses: { id: number; name: string; qty: number }[];
};

const props = defineProps<{
    rows: Row[];
    /** في أصناف أصلاً؟ بيفرّق بين «لسه مافيش» و«الفلتر مش جايب نتيجة» */
    hasAny: boolean;
    warehouses: { id: number; name: string }[];
    filters: { search?: string; warehouse_id?: string; low?: string };
    summary: { items: number; units: number; value: number; low: number };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    search: props.filters.search ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    low: props.filters.low ?? '',
});

function apply() {
    router.get(
        '/inventory',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watchDebounced(() => filters.search, apply, { debounce: 350 });
</script>

<template>
    <Head title="المخزون" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="المخزون"
            description="أرصدة الأصناف في كل مخزن وقيمتها بالتكلفة"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">عدد الأصناف</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatNumber(summary.items) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">إجمالي الوحدات</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatNumber(summary.units) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">
                    قيمة المخزون بالتكلفة
                </p>
                <p
                    class="mt-2 text-xl font-bold tabular-nums"
                    data-test="inventory-value"
                >
                    {{ formatMoney(summary.value) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">تحت حد الطلب</p>
                <p
                    class="mt-2 text-xl font-bold text-status-critical tabular-nums"
                >
                    {{ formatNumber(summary.low) }}
                </p>
            </div>
        </div>

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute inset-y-0 end-3 my-auto size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="filters.search"
                            type="search"
                            placeholder="اسم الصنف أو الكود"
                            class="w-56 pe-9"
                            data-test="inventory-search"
                        />
                    </div>

                    <select
                        v-model="filters.warehouse_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
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

                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-input px-3 py-1.5 text-sm"
                    >
                        <input
                            v-model="filters.low"
                            type="checkbox"
                            true-value="1"
                            false-value=""
                            class="accent-primary"
                            data-test="inventory-low"
                            @change="apply"
                        />
                        تحت حد الطلب بس
                    </label>
                </div>
            </template>

            <div v-if="rows.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الصنف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التصنيف
                            </th>
                            <th
                                v-for="w in warehouses"
                                :key="w.id"
                                class="px-5 py-2.5 text-start font-medium"
                            >
                                {{ w.name }}
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                حد الطلب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                القيمة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                            :class="row.isLow ? 'bg-status-critical/5' : ''"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="`/inventory/${row.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ row.name }}
                                </Link>
                                <span
                                    class="ms-1 text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ row.sku }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.category ?? '—' }}
                            </td>
                            <td
                                v-for="w in row.warehouses"
                                :key="w.id"
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatNumber(w.qty) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatQuantity(row.total, row.unit) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                                :class="
                                    row.isLow
                                        ? 'font-semibold text-status-critical'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatNumber(row.minStock) }}
                                <span v-if="row.isLow" class="text-xs"
                                    >· ناقص</span
                                >
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.value) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else-if="hasAny"
                :icon="Package"
                title="مفيش أصناف مطابقة"
                description="جرّب تغيّر البحث أو تشيل الفلاتر."
                :secondary="{ label: 'امسح الفلاتر', href: '/inventory' }"
            />

            <EmptyState
                v-else
                large
                :icon="Package"
                title="لسه مافيش أصناف"
                description="رصيد المخزن بيتكوّن من حركات الشراء والبيع — سجّل أول فاتورة شراء والأصناف هتظهر هنا برصيدها وتكلفتها."
                :action="{
                    label: 'فاتورة شراء جديدة',
                    href: '/purchases/create',
                }"
            />
        </SectionCard>
    </div>
</template>
