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
    warehouse: string | null;
    category: string | null;
    status: string;
    statusLabel: string;
    itemCount: number;
    createdBy: string | null;
};

const props = defineProps<{
    counts: { data: Row[] } & PaginatorMeta;
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    warehouses: { id: number; name: string }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    status: props.filters.status ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    search: props.filters.search ?? '',
});

function apply() {
    router.get(
        '/stock-counts',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watchDebounced(() => filters.search, apply, { debounce: 350 });

const STATUS_TONE: Record<string, string> = {
    open: 'bg-status-warning/10 text-status-warning',
    approved: 'bg-status-good/10 text-status-good',
    cancelled: 'bg-muted text-muted-foreground',
};

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="الجرد" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="الجرد"
            :description="`${formatNumber(counts.total)} ورقة جرد`"
        >
            <template #actions>
                <Button as-child>
                    <Link href="/stock-counts/create">
                        <Plus class="size-4" />
                        جرد جديد
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
                            placeholder="رقم الجرد"
                            data-test="count-search"
                        />
                    </div>
                    <select
                        v-model="filters.status"
                        :class="SELECT_CLASS"
                        data-test="filter-status"
                        @change="apply"
                    >
                        <option value="">كل الحالات</option>
                        <option value="open">مفتوح</option>
                        <option value="approved">معتمد</option>
                        <option value="cancelled">ملغي</option>
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
                title="مفيش جرد لسه"
                description="الجرد بيقارن اللي على الرف باللي في النظام، والاعتماد بيولّد أذون الإضافة والخصم للفروق لوحده."
            />

            <EmptyState
                v-else-if="!counts.data.length"
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
                                رقم الجرد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التصنيف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                أصناف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الحالة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                فتحها
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in counts.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium whitespace-nowrap">
                                <Link
                                    :href="`/stock-counts/${row.id}`"
                                    class="tabular-nums hover:underline"
                                    :data-test="`count-${row.number}`"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3">
                                {{ row.warehouse ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.category ?? 'كل الأصناف' }}
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
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.createdBy ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="counts.data.length" :meta="counts" />
        </SectionCard>
    </div>
</template>
