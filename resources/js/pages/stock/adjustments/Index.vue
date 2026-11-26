<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardCheck, Minus, Plus, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateShort, formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    number: string;
    type: string;
    typeLabel: string;
    date: string;
    warehouse: string | null;
    reason: string;
    status: string;
    isApproved: boolean;
    itemCount: number;
    value: number;
    createdBy: string | null;
};

const props = defineProps<{
    adjustments: { data: Row[] } & PaginatorMeta;
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    reasons: { value: string; label: string }[];
    warehouses: { id: number; name: string }[];
    canApprove: boolean;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
    reason: props.filters.reason ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    search: props.filters.search ?? '',
});

function apply() {
    router.get(
        '/stock-adjustments',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watchDebounced(() => filters.search, apply, { debounce: 350 });

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="أذون المخزن" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="أذون المخزن"
            :description="`${formatNumber(adjustments.total)} إذن`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/stock-adjustments/create?type=addition">
                        <Plus class="size-4" />
                        إذن إضافة
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/stock-adjustments/create?type=deduction">
                        <Minus class="size-4" />
                        إذن خصم
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
                            class="h-9 w-44 ps-8"
                            placeholder="رقم الإذن"
                            data-test="adjustment-search"
                        />
                    </div>
                    <select
                        v-model="filters.type"
                        :class="SELECT_CLASS"
                        data-test="filter-type"
                        @change="apply"
                    >
                        <option value="">كل الأنواع</option>
                        <option value="addition">إذن إضافة</option>
                        <option value="deduction">إذن خصم</option>
                    </select>
                    <select
                        v-model="filters.status"
                        :class="SELECT_CLASS"
                        data-test="filter-status"
                        @change="apply"
                    >
                        <option value="">كل الحالات</option>
                        <option value="draft">مسودة</option>
                        <option value="approved">معتمد</option>
                    </select>
                    <select
                        v-model="filters.reason"
                        :class="SELECT_CLASS"
                        @change="apply"
                    >
                        <option value="">كل الأسباب</option>
                        <option
                            v-for="r in reasons"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
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
                :icon="ClipboardCheck"
                title="مفيش أذون مخزن لسه"
                description="إذن الإضافة والخصم بيصحّحوا الرصيد — تالف، فقد، هدية، أو رصيد أول المدة. بيتحفظوا مسودة والمخزون مابيتحركش غير بعد الاعتماد."
            />

            <EmptyState
                v-else-if="!adjustments.data.length"
                :icon="Search"
                title="الفلتر مش جايب نتيجة"
                description="غيّر الفلتر أو امسحه."
            />

            <div v-else class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                رقم الإذن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                النوع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                السبب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                أصناف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                القيمة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الحالة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in adjustments.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium whitespace-nowrap">
                                <Link
                                    :href="`/stock-adjustments/${row.id}`"
                                    class="tabular-nums hover:underline"
                                    :data-test="`adjustment-${row.number}`"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-md px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.type === 'addition'
                                            ? 'bg-status-good/10 text-status-good'
                                            : 'bg-status-warning/10 text-status-warning'
                                    "
                                >
                                    {{ row.typeLabel }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.warehouse ?? '—' }}
                            </td>
                            <td class="px-5 py-3">{{ row.reason }}</td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(row.itemCount) }}
                            </td>
                            <td class="px-5 py-3 font-semibold tabular-nums">
                                {{ formatMoney(row.value) }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-md px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.isApproved
                                            ? 'bg-status-good/10 text-status-good'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ row.isApproved ? 'معتمد' : 'مسودة' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="adjustments.data.length" :meta="adjustments" />
        </SectionCard>
    </div>
</template>
