<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Receipt, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import EtaBadge from '@/components/EtaBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import ScanInvoiceDialog from '@/components/ScanInvoiceDialog.vue';
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
    status: string;
    date: string;
    customer: string | null;
    warehouse: string | null;
    total: number;
    paid: number;
    remaining: number;
    etaStatus: string | null;
};

const props = defineProps<{
    invoices: { data: Row[] } & PaginatorMeta;
    totals: { count: number; total: number; paid: number; outstanding: number };
    /** في فواتير بيع أصلاً؟ بيفرّق بين «لسه مابدأتش» و«الفلتر مش جايب نتيجة» */
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    customers: { id: number; name: string }[];
    warehouses: { id: number; name: string }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const page = usePage();

const aiEnabled = computed(() => page.props.aiEnabled === true);

const filters = reactive({
    search: props.filters.search ?? '',
    customer_id: props.filters.customer_id ?? '',
    warehouse_id: props.filters.warehouse_id ?? '',
    eta: props.filters.eta ?? '',
    payment: props.filters.payment ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/sales',
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
    <Head title="المبيعات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="المبيعات"
            :description="`${formatNumber(totals.count)} فاتورة في النتيجة الحالية`"
        >
            <template #actions>
                <ScanInvoiceDialog v-if="aiEnabled" type="sale" />
                <Button as-child>
                    <Link href="/sales/create">
                        <Plus class="size-4" />
                        فاتورة بيع جديدة
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <!-- إجماليات الفلتر الحالي -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div
                class="rounded-xl border border-border bg-card p-4 shadow-soft"
            >
                <p class="text-xs text-muted-foreground">إجمالي الفواتير</p>
                <p class="mt-1 text-lg font-bold tabular-nums">
                    {{ formatMoney(totals.total) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-4 shadow-soft"
            >
                <p class="text-xs text-muted-foreground">المحصّل</p>
                <p class="mt-1 text-lg font-bold text-status-good tabular-nums">
                    {{ formatMoney(totals.paid) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-4 shadow-soft"
            >
                <p class="text-xs text-muted-foreground">المتبقي</p>
                <p class="mt-1 text-lg font-bold tabular-nums">
                    {{ formatMoney(totals.outstanding) }}
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
                            placeholder="رقم الفاتورة أو اسم التاجر"
                            class="w-56 pe-9"
                            data-test="sales-search"
                        />
                    </div>

                    <select
                        v-model="filters.customer_id"
                        :class="SELECT_CLASS"
                        data-test="sales-customer"
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

                    <select
                        v-model="filters.eta"
                        :class="SELECT_CLASS"
                        data-test="sales-eta"
                        @change="apply"
                    >
                        <option value="">كل حالات المنظومة</option>
                        <option value="none">لم تُرسل</option>
                        <option value="sent">مُرسلة</option>
                        <option value="submitted">قيد المراجعة</option>
                        <option value="valid">مقبولة</option>
                        <option value="rejected">مرفوضة</option>
                    </select>

                    <select
                        v-model="filters.payment"
                        :class="SELECT_CLASS"
                        @change="apply"
                    >
                        <option value="">كل حالات السداد</option>
                        <option value="unpaid">عليها متبقي</option>
                        <option value="paid">مسدّدة</option>
                    </select>

                    <select
                        v-model="filters.status"
                        :class="SELECT_CLASS"
                        data-test="sales-status"
                        @change="apply"
                    >
                        <option value="">كل الفواتير</option>
                        <option value="confirmed">المؤكّدة</option>
                        <option value="draft">المسودات</option>
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
                                التاجر
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المتبقي
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الفاتورة الإلكترونية
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
                                <!-- المسودة بتودّي على صفحة المراجعة مش العرض -->
                                <Link
                                    :href="
                                        row.status === 'draft'
                                            ? `/sales/drafts/${row.id}`
                                            : `/sales/${row.id}`
                                    "
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ row.number }}
                                </Link>
                                <span
                                    v-if="row.status === 'draft'"
                                    class="ms-2 rounded-full bg-yellow-400/15 px-1.5 py-0.5 text-[10px] font-semibold text-yellow-700 dark:text-yellow-500"
                                    data-test="draft-badge"
                                >
                                    مسودة
                                </span>
                                <span
                                    v-if="row.type === 'sale_return'"
                                    class="ms-2 rounded-full bg-status-warning/10 px-1.5 py-0.5 text-[10px] font-semibold text-status-warning"
                                >
                                    مرتجع
                                </span>
                            </td>
                            <td class="max-w-48 truncate px-5 py-3">
                                {{ row.customer ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ row.warehouse ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.total) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                                :class="
                                    row.remaining > 0
                                        ? 'text-status-critical'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    row.remaining > 0
                                        ? formatMoney(row.remaining)
                                        : 'مسدّدة'
                                }}
                            </td>
                            <td class="px-5 py-3">
                                <EtaBadge
                                    v-if="row.type === 'sale'"
                                    :status="row.etaStatus"
                                />
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >لا ينطبق</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else-if="hasAny"
                :icon="Receipt"
                title="مفيش فواتير مطابقة للفلاتر"
                description="جرّب توسّع الفترة أو تشيل الفلاتر."
                :secondary="{ label: 'امسح الفلاتر', href: '/sales' }"
            />

            <EmptyState
                v-else
                large
                :icon="Receipt"
                title="لسه مافيش فواتير بيع"
                description="أول ما تعمل فاتورة بيع، الكميات هتتخصم من المخزن ورصيد التاجر هيتحدّث تلقائيًا."
                :action="{ label: 'فاتورة بيع جديدة', href: '/sales/create' }"
            />

            <Pagination :meta="invoices" />
        </SectionCard>
    </div>
</template>
