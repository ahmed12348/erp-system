<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, Undo2 } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive } from 'vue';
import DownloadMenu from '@/components/DownloadMenu.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAbilities } from '@/composables/useAbilities';
import { formatDateShort, formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    number: string;
    date: string;
    party: string | null;
    warehouse: string | null;
    original: string | null;
    originalId: number | null;
    reason: string | null;
    isOrphan: boolean;
    total: number;
};

const props = defineProps<{
    returns: { data: Row[] } & PaginatorMeta;
    type: string;
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    reasons: { value: string; label: string }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/** المرتجع من غير فاتورة أصلية للمدير بس */
const { manageUsers } = useAbilities();

const isPurchase = computed(() => props.type === 'purchase_return');

const filters = reactive({
    type: props.type,
    search: props.filters.search ?? '',
    reason: props.filters.reason ?? '',
    orphan: props.filters.orphan ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function query(): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    ) as Record<string, string>;
}

function apply() {
    router.get('/returns', query(), {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

watchDebounced(() => filters.search, apply, { debounce: 350 });

const exportHref = computed(
    () => `/exports/returns?${new URLSearchParams(query()).toString()}`,
);

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head :title="isPurchase ? 'مرتجعات المشتريات' : 'مرتجعات المبيعات'" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="isPurchase ? 'مرتجعات المشتريات' : 'مرتجعات المبيعات'"
            :description="`${formatNumber(returns.total)} مرتجع`"
        >
            <template #actions>
                <DownloadMenu :href="exportHref" label="تحميل القائمة" />
                <Button v-if="manageUsers" variant="outline" as-child>
                    <Link :href="`/returns/orphan?type=${type}`">
                        <Plus class="size-4" />
                        مرتجع من غير فاتورة
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="filters.type"
                        :class="SELECT_CLASS"
                        data-test="return-type"
                        @change="apply"
                    >
                        <option value="sale_return">مرتجعات المبيعات</option>
                        <option value="purchase_return">
                            مرتجعات المشتريات
                        </option>
                    </select>

                    <div class="relative">
                        <Search
                            class="absolute start-2.5 top-2.5 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="filters.search"
                            class="h-9 w-52 ps-8"
                            placeholder="رقم المرتجع أو الفاتورة"
                            data-test="return-search"
                        />
                    </div>

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
                        v-model="filters.orphan"
                        :class="SELECT_CLASS"
                        data-test="filter-orphan"
                        @change="apply"
                    >
                        <option value="">الكل</option>
                        <option value="1">من غير فاتورة أصلية</option>
                    </select>
                </div>
            </template>

            <EmptyState
                v-if="!hasAny"
                :icon="Undo2"
                title="مفيش مرتجعات لسه"
                description="المرتجع بيتعمل من زرار «مرتجع» على الفاتورة نفسها — بيرجّع البضاعة للمخزن ويقلّل الرصيد."
            />

            <EmptyState
                v-else-if="!returns.data.length"
                :icon="Search"
                title="الفلتر مش جايب نتيجة"
                description="غيّر الفلتر أو امسحه عشان تشوف باقي المرتجعات."
            />

            <div v-else class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                رقم المرتجع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الفاتورة الأصلية
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                {{ isPurchase ? 'المورد' : 'التاجر' }}
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                السبب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in returns.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium whitespace-nowrap">
                                <Link
                                    :href="`/returns/${row.id}`"
                                    class="tabular-nums hover:underline"
                                    :data-test="`return-${row.number}`"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <Link
                                    v-if="row.originalId"
                                    :href="`${isPurchase ? '/purchases/' : '/sales/'}${row.originalId}`"
                                    class="tabular-nums hover:underline"
                                >
                                    {{ row.original }}
                                </Link>
                                <!-- المرتجع اليتيم لازم يبان إنه كده -->
                                <span
                                    v-else
                                    class="rounded-md bg-status-warning/10 px-2 py-0.5 text-xs font-medium text-status-warning"
                                    data-test="orphan-badge"
                                >
                                    من غير فاتورة
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ row.party ?? '—' }}</td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.warehouse ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.reason ?? '—' }}
                            </td>
                            <td class="px-5 py-3 font-semibold tabular-nums">
                                {{ formatMoney(row.total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="returns.data.length" :meta="returns" />
        </SectionCard>
    </div>
</template>
