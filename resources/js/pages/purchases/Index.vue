<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, ShoppingCart } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive } from 'vue';
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
    date: string;
    supplier: string | null;
    country: string | null;
    warehouse: string | null;
    currency: string;
    exchangeRate: number;
    total: number;
    totalEgp: number;
    landed: number;
    remaining: number;
};

const props = defineProps<{
    invoices: { data: Row[] } & PaginatorMeta;
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    suppliers: { id: number; name: string }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    search: props.filters.search ?? '',
    supplier_id: props.filters.supplier_id ?? '',
    currency: props.filters.currency ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/purchases',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watchDebounced(() => filters.search, apply, { debounce: 350 });

/** إجمالي الصفحة بالجنيه — العملات مختلفة فالجمع لازم يكون بعد التحويل */
const pageTotalEgp = computed(() =>
    props.invoices.data.reduce((sum, row) => sum + row.totalEgp, 0),
);

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="المشتريات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="المشتريات"
            :description="`${formatNumber(invoices.total)} فاتورة استيراد`"
        >
            <template #actions>
                <Button as-child>
                    <Link href="/purchases/create">
                        <Plus class="size-4" />
                        فاتورة شراء جديدة
                    </Link>
                </Button>
            </template>
        </PageHeader>

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
                            placeholder="رقم الفاتورة أو المورد"
                            class="w-56 pe-9"
                            data-test="purchases-search"
                        />
                    </div>

                    <select
                        v-model="filters.supplier_id"
                        :class="SELECT_CLASS"
                        @change="apply"
                    >
                        <option value="">كل الموردين</option>
                        <option
                            v-for="s in suppliers"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.name }}
                        </option>
                    </select>

                    <select
                        v-model="filters.currency"
                        :class="SELECT_CLASS"
                        data-test="purchases-currency"
                        @change="apply"
                    >
                        <option value="">كل العملات</option>
                        <option value="USD">دولار أمريكي</option>
                        <option value="EUR">يورو</option>
                        <option value="TRY">ليرة تركية</option>
                        <option value="EGP">جنيه مصري</option>
                    </select>

                    <Input
                        v-model="filters.from"
                        type="date"
                        class="h-9 w-38"
                        aria-label="من تاريخ"
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

            <div
                v-if="invoices.data.length"
                class="scrollbar-thin overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                رقم الفاتورة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المورد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                العملة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                سعر الصرف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                بالجنيه
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                مصاريف الاستيراد
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in invoices.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 font-medium whitespace-nowrap tabular-nums"
                            >
                                <Link
                                    :href="`/purchases/${row.id}`"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ row.number }}
                                </Link>
                            </td>
                            <td class="max-w-48 truncate px-5 py-3">
                                {{ row.supplier ?? '—' }}
                                <span class="text-xs text-muted-foreground">
                                    · {{ row.country }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ row.currency }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatNumber(row.exchangeRate, 4) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.total, row.currency, 2) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.totalEgp) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{
                                    row.landed > 0
                                        ? formatMoney(row.landed)
                                        : '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40">
                        <tr class="font-semibold">
                            <td colspan="6" class="px-5 py-3">
                                إجمالي الصفحة بالجنيه
                            </td>
                            <td class="px-5 py-3 tabular-nums" colspan="2">
                                {{ formatMoney(pageTotalEgp) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <EmptyState
                v-else-if="hasAny"
                :icon="ShoppingCart"
                title="مفيش فواتير شراء مطابقة للفلاتر"
                description="جرّب توسّع الفترة أو تشيل الفلاتر."
                :secondary="{ label: 'امسح الفلاتر', href: '/purchases' }"
            />

            <EmptyState
                v-else
                large
                :icon="ShoppingCart"
                title="لسه مافيش فواتير شراء"
                description="سجّل فاتورة الاستيراد بعملتها وسعر الصرف ومصاريف الشحن والجمارك، والنظام هيوزّعها على الأصناف ويحدّث التكلفة والمخزون."
                :action="{
                    label: 'فاتورة شراء جديدة',
                    href: '/purchases/create',
                }"
            />

            <Pagination :meta="invoices" />
        </SectionCard>
    </div>
</template>
