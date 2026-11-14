<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Coins,
    PackageX,
    TrendingUp,
    Wallet,
    Warehouse,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EtaBadge from '@/components/EtaBadge.vue';
import KpiCard from '@/components/dashboard/KpiCard.vue';
import SalesChart from '@/components/dashboard/SalesChart.vue';
import type { SeriesPoint } from '@/components/dashboard/SalesChart.vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    formatCompactMoney,
    formatDateShort,
    formatMoney,
    formatNumber,
    formatQuantity,
} from '@/lib/format';

type Kpis = {
    sales: {
        value: number;
        previous: number;
        change: number | null;
        periodDays: number;
    };
    receivables: { value: number; traders: number };
    inventoryValue: { value: number };
    lowStock: { value: number };
};

type TopProduct = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    category: string | null;
    quantity: number;
    revenue: number;
};

type TopDebtor = {
    id: number;
    name: string;
    city: string | null;
    phone: string | null;
    balance: number;
    creditLimit: number | null;
};

type LatestInvoice = {
    id: number;
    number: string;
    type: string;
    date: string;
    party: string | null;
    currency: string;
    total: number;
    remaining: number;
    etaStatus: string | null;
};

const props = defineProps<{
    kpis: Kpis;
    series: { daily: SeriesPoint[]; weekly: SeriesPoint[] };
    topProducts: TopProduct[];
    topDebtors: TopDebtor[];
    latestInvoices: LatestInvoice[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [{ title: 'لوحة التحكم', href: '/dashboard' }],
    }),
});

const granularity = ref<'daily' | 'weekly'>('daily');

const chartPoints = computed(() =>
    granularity.value === 'daily' ? props.series.daily : props.series.weekly,
);

const chartTotal = computed(() =>
    props.series.daily.reduce((sum, point) => sum + point.total, 0),
);

const INVOICE_TYPES: Record<string, string> = {
    sale: 'فاتورة بيع',
    purchase: 'فاتورة شراء',
    sale_return: 'مرتجع بيع',
    purchase_return: 'مرتجع شراء',
};

/** أعلى قيمة مبيعات في القائمة — بنرسم عليها شريط النسبة */
const maxRevenue = computed(() =>
    Math.max(...props.topProducts.map((product) => product.revenue), 1),
);

const maxBalance = computed(() =>
    Math.max(...props.topDebtors.map((debtor) => debtor.balance), 1),
);
</script>

<template>
    <Head title="لوحة التحكم" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="لوحة التحكم"
            description="نظرة سريعة على المبيعات والمخزون ومديونيات التجار"
        />

        <!-- المؤشرات -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
                label="مبيعات الشهر الحالي"
                :value="formatMoney(kpis.sales.value)"
                :change="kpis.sales.change"
                :change-note="`مقارنة بأول ${formatNumber(kpis.sales.periodDays)} يوم من الشهر الماضي`"
                href="/sales"
                :icon="TrendingUp"
            />
            <KpiCard
                label="مديونيات التجار"
                :value="formatMoney(kpis.receivables.value)"
                :hint="`موزّعة على ${formatNumber(kpis.receivables.traders)} تاجر`"
                href="/customers"
                :icon="Wallet"
            />
            <KpiCard
                label="قيمة المخزون بالتكلفة"
                :value="formatMoney(kpis.inventoryValue.value)"
                hint="بسعر الشراء الواصل"
                href="/inventory"
                :icon="Warehouse"
            />
            <KpiCard
                label="أصناف تحت حد الطلب"
                :value="formatNumber(kpis.lowStock.value)"
                hint="محتاجة إعادة طلب"
                href="/inventory"
                :icon="PackageX"
                tone="warning"
            />
        </div>

        <!-- الرسم البياني -->
        <section
            class="rounded-xl border border-border bg-card p-5 shadow-soft"
        >
            <div
                class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-1">
                    <h2 class="text-base font-semibold">مبيعات آخر ٣ شهور</h2>
                    <p class="text-sm text-muted-foreground">
                        إجمالي الفترة
                        <span class="font-semibold text-foreground">
                            {{ formatMoney(chartTotal) }}
                        </span>
                    </p>
                </div>

                <!-- التبديل بين اليومي والأسبوعي -->
                <div
                    class="inline-flex shrink-0 self-start rounded-lg bg-muted p-1"
                    role="group"
                    aria-label="تجميع المبيعات"
                >
                    <button
                        v-for="option in [
                            { key: 'daily', label: 'يومي' },
                            { key: 'weekly', label: 'أسبوعي' },
                        ]"
                        :key="option.key"
                        type="button"
                        class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                        :class="
                            granularity === option.key
                                ? 'bg-card text-foreground shadow-soft'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :aria-pressed="granularity === option.key"
                        :data-test="`granularity-${option.key}`"
                        @click="granularity = option.key as 'daily' | 'weekly'"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <SalesChart :points="chartPoints" :granularity="granularity" />
        </section>

        <!-- الأصناف والتجار -->
        <div class="grid gap-5 lg:grid-cols-2">
            <section
                class="rounded-xl border border-border bg-card shadow-soft"
            >
                <header
                    class="flex items-center justify-between border-b border-border px-5 py-4"
                >
                    <h2 class="text-base font-semibold">أعلى ٥ أصناف مبيعًا</h2>
                    <Link
                        href="/inventory"
                        class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary"
                    >
                        كل الأصناف
                        <ArrowLeft class="size-3.5" />
                    </Link>
                </header>

                <ul class="divide-y divide-border">
                    <li v-for="(product, i) in topProducts" :key="product.id">
                        <Link
                            href="/inventory"
                            class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-accent/60"
                        >
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-bold text-muted-foreground"
                            >
                                {{ formatNumber(i + 1) }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ product.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ product.category ?? 'بدون تصنيف' }} ·
                                    {{
                                        formatQuantity(
                                            product.quantity,
                                            product.unit,
                                        )
                                    }}
                                </p>
                                <!-- شريط نسبي بدل رقم على كل سطر -->
                                <div
                                    class="mt-1.5 h-1 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-chart-1"
                                        :style="{
                                            width: `${(product.revenue / maxRevenue) * 100}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <span
                                class="shrink-0 text-sm font-semibold tabular-nums"
                            >
                                {{ formatCompactMoney(product.revenue) }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section
                class="rounded-xl border border-border bg-card shadow-soft"
            >
                <header
                    class="flex items-center justify-between border-b border-border px-5 py-4"
                >
                    <h2 class="text-base font-semibold">أعلى ٥ تجار مديونية</h2>
                    <Link
                        href="/customers"
                        class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary"
                    >
                        كل التجار
                        <ArrowLeft class="size-3.5" />
                    </Link>
                </header>

                <ul class="divide-y divide-border">
                    <li v-for="debtor in topDebtors" :key="debtor.id">
                        <Link
                            :href="`/customers?trader=${debtor.id}`"
                            class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-accent/60"
                        >
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-md bg-accent text-xs font-bold text-accent-foreground"
                            >
                                <Coins class="size-3.5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ debtor.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ debtor.city ?? '—' }}
                                    <template v-if="debtor.creditLimit">
                                        · الحد
                                        {{
                                            formatCompactMoney(
                                                debtor.creditLimit,
                                            )
                                        }}
                                    </template>
                                </p>
                                <div
                                    class="mt-1.5 h-1 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        :class="
                                            debtor.creditLimit &&
                                            debtor.balance > debtor.creditLimit
                                                ? 'bg-status-critical'
                                                : 'bg-chart-2'
                                        "
                                        :style="{
                                            width: `${(debtor.balance / maxBalance) * 100}%`,
                                        }"
                                    />
                                </div>
                            </div>

                            <span
                                class="shrink-0 text-sm font-semibold tabular-nums"
                            >
                                {{ formatCompactMoney(debtor.balance) }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <!-- آخر الفواتير -->
        <section class="rounded-xl border border-border bg-card shadow-soft">
            <header
                class="flex items-center justify-between border-b border-border px-5 py-4"
            >
                <h2 class="text-base font-semibold">آخر الفواتير</h2>
                <Link
                    href="/sales"
                    class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary"
                >
                    كل الفواتير
                    <ArrowLeft class="size-3.5" />
                </Link>
            </header>

            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                رقم الفاتورة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                النوع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الطرف
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
                            v-for="invoice in latestInvoices"
                            :key="invoice.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 font-medium whitespace-nowrap tabular-nums"
                            >
                                {{ invoice.number }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{
                                    INVOICE_TYPES[invoice.type] ?? invoice.type
                                }}
                            </td>
                            <td class="max-w-56 truncate px-5 py-3">
                                {{ invoice.party ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(invoice.date) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{
                                    formatMoney(invoice.total, invoice.currency)
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                                :class="
                                    invoice.remaining > 0
                                        ? 'text-status-critical'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    invoice.remaining > 0
                                        ? formatMoney(
                                              invoice.remaining,
                                              invoice.currency,
                                          )
                                        : 'مسدّدة'
                                }}
                            </td>
                            <td class="px-5 py-3">
                                <EtaBadge
                                    v-if="invoice.type === 'sale'"
                                    :status="invoice.etaStatus"
                                />
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    لا ينطبق
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
