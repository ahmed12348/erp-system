<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, MapPin, Phone, Printer, Receipt } from '@lucide/vue';
import { ref } from 'vue';
import RecordPaymentDialog from '@/components/customers/RecordPaymentDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import EtaBadge from '@/components/EtaBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    formatDateShort,
    formatMoney,
    formatNumber,
    formatPercent,
} from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type StatementRow = {
    id: number;
    kind: string;
    date: string;
    reference: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
    link: string | null;
};

const props = defineProps<{
    customer: {
        id: number;
        name: string;
        phone: string | null;
        city: string | null;
        address: string | null;
        priceTier: string;
        openingBalance: number;
        notes: string | null;
    };
    summary: {
        sales: number;
        returns: number;
        paid: number;
        balance: number;
        invoiceCount: number;
        creditLimit: number | null;
        creditUsage: number | null;
        overLimit: boolean;
    };
    statement: {
        opening: number;
        rows: StatementRow[];
        closing: number;
        totals: { debit: number; credit: number };
    };
    invoices: {
        id: number;
        number: string;
        type: string;
        date: string;
        total: number;
        paid: number;
        remaining: number;
        etaStatus: string | null;
    }[];
    openInvoices: { id: number; label: string }[];
    filters: { from: string | null; to: string | null };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

function applyDates() {
    router.get(
        `/customers/${props.customer.id}`,
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearDates() {
    from.value = '';
    to.value = '';
    applyDates();
}

function openStatement() {
    const params = new URLSearchParams();
    if (from.value) params.set('from', from.value);
    if (to.value) params.set('to', to.value);

    window.open(
        `/customers/${props.customer.id}/statement?${params.toString()}`,
        '_blank',
    );
}

const KIND_TONE: Record<string, string> = {
    sale: 'text-foreground',
    sale_return: 'text-status-warning',
    payment: 'text-status-good',
};
</script>

<template>
    <Head :title="customer.name" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="customer.name"
            :description="customer.city ?? undefined"
        >
            <template #actions>
                <Button variant="outline" @click="openStatement">
                    <Printer class="size-4" />
                    طباعة كشف الحساب
                </Button>
                <RecordPaymentDialog
                    :customer-id="customer.id"
                    :customer-name="customer.name"
                    :balance="summary.balance"
                    :open-invoices="openInvoices"
                />
            </template>
        </PageHeader>

        <!-- بيانات + مؤشرات -->
        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="بيانات التاجر">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <Phone class="size-4 shrink-0 text-muted-foreground" />
                        <dd dir="ltr" class="tabular-nums">
                            {{ customer.phone ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex items-start gap-2">
                        <MapPin
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <dd>
                            {{ customer.city ?? '—' }}
                            <span
                                v-if="customer.address"
                                class="text-muted-foreground"
                            >
                                — {{ customer.address }}
                            </span>
                        </dd>
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-border pt-3"
                    >
                        <dt class="text-muted-foreground">شريحة السعر</dt>
                        <dd class="font-medium">
                            {{
                                customer.priceTier === 'wholesale'
                                    ? 'جملة الجملة'
                                    : 'تجزئة'
                            }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">رصيد افتتاحي</dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatMoney(customer.openingBalance) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted-foreground">عدد الفواتير</dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatNumber(summary.invoiceCount) }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                <div
                    class="rounded-xl border border-border bg-card p-5 shadow-soft"
                >
                    <p class="text-sm text-muted-foreground">إجمالي المبيعات</p>
                    <p class="mt-2 text-xl font-bold tabular-nums">
                        {{ formatMoney(summary.sales) }}
                    </p>
                    <p
                        v-if="summary.returns > 0"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        مرتجعات {{ formatMoney(summary.returns) }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-border bg-card p-5 shadow-soft"
                >
                    <p class="text-sm text-muted-foreground">
                        إجمالي التحصيلات
                    </p>
                    <p
                        class="mt-2 text-xl font-bold text-status-good tabular-nums"
                    >
                        {{ formatMoney(summary.paid) }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-border bg-card p-5 shadow-soft"
                >
                    <p class="text-sm text-muted-foreground">الرصيد المستحق</p>
                    <p
                        class="mt-2 text-xl font-bold tabular-nums"
                        :class="
                            summary.balance > 0
                                ? 'text-foreground'
                                : 'text-status-good'
                        "
                        data-test="customer-balance"
                    >
                        {{ formatMoney(summary.balance) }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-border bg-card p-5 shadow-soft"
                >
                    <p class="text-sm text-muted-foreground">
                        استهلاك حد المديونية
                    </p>
                    <template v-if="summary.creditLimit">
                        <p
                            class="mt-2 text-xl font-bold tabular-nums"
                            :class="
                                summary.overLimit
                                    ? 'text-status-critical'
                                    : 'text-foreground'
                            "
                        >
                            {{
                                formatPercent(
                                    (summary.creditUsage ?? 0) / 100,
                                    0,
                                )
                            }}
                        </p>
                        <div
                            class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full"
                                :class="
                                    summary.overLimit
                                        ? 'bg-status-critical'
                                        : 'bg-chart-1'
                                "
                                :style="{
                                    width: `${Math.min(summary.creditUsage ?? 0, 100)}%`,
                                }"
                            />
                        </div>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            الحد {{ formatMoney(summary.creditLimit) }}
                            <span
                                v-if="summary.overLimit"
                                class="font-semibold text-status-critical"
                            >
                                — تعدّى الحد
                            </span>
                        </p>
                    </template>
                    <p v-else class="mt-2 text-sm text-muted-foreground">
                        بدون حد مديونية
                    </p>
                </div>
            </div>
        </div>

        <!-- كشف الحساب -->
        <SectionCard title="كشف الحساب" flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="from"
                        type="date"
                        class="h-9 w-40"
                        aria-label="من تاريخ"
                        data-test="statement-from"
                        @change="applyDates"
                    />
                    <span class="text-xs text-muted-foreground">إلى</span>
                    <Input
                        v-model="to"
                        type="date"
                        class="h-9 w-40"
                        aria-label="إلى تاريخ"
                        data-test="statement-to"
                        @change="applyDates"
                    />
                    <Button
                        v-if="from || to"
                        variant="ghost"
                        size="sm"
                        @click="clearDates"
                    >
                        مسح
                    </Button>
                </div>
            </template>

            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                البيان
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المرجع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                مدين
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                دائن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الرصيد
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr class="bg-muted/40">
                            <td
                                class="px-5 py-2.5 text-muted-foreground"
                                colspan="5"
                            >
                                رصيد ما قبل الفترة
                            </td>
                            <td class="px-5 py-2.5 font-semibold tabular-nums">
                                {{ formatMoney(statement.opening) }}
                            </td>
                        </tr>

                        <tr
                            v-for="row in statement.rows"
                            :key="`${row.kind}-${row.id}`"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3" :class="KIND_TONE[row.kind]">
                                <Link
                                    v-if="row.link"
                                    :href="row.link"
                                    class="hover:underline"
                                >
                                    {{ row.description }}
                                </Link>
                                <span v-else>{{ row.description }}</span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ row.reference }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{
                                    row.debit > 0 ? formatMoney(row.debit) : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-status-good tabular-nums"
                            >
                                {{
                                    row.credit > 0
                                        ? formatMoney(row.credit)
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.balance) }}
                            </td>
                        </tr>

                        <tr v-if="!statement.rows.length">
                            <td colspan="6" class="p-0">
                                <EmptyState
                                    :icon="Receipt"
                                    title="مفيش حركة في الفترة دي"
                                    description="غيّر الفترة أو امسح الفلتر عشان تشوف كل الحركة."
                                />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        v-if="statement.rows.length"
                        class="border-t-2 border-border bg-muted/40"
                    >
                        <tr class="font-semibold">
                            <td class="px-5 py-3" colspan="3">الإجمالي</td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(statement.totals.debit) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(statement.totals.credit) }}
                            </td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                data-test="statement-closing"
                            >
                                {{ formatMoney(statement.closing) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </SectionCard>

        <!-- فواتيره -->
        <SectionCard title="فواتير التاجر" flush>
            <div v-if="invoices.length" class="scrollbar-thin overflow-x-auto">
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
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المدفوع
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
                            v-for="invoice in invoices"
                            :key="invoice.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 font-medium whitespace-nowrap tabular-nums"
                            >
                                <Link
                                    v-if="invoice.type === 'sale'"
                                    :href="`/sales/${invoice.id}`"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ invoice.number }}
                                </Link>
                                <span v-else>{{ invoice.number }}</span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{
                                    invoice.type === 'sale'
                                        ? 'فاتورة بيع'
                                        : 'مرتجع بيع'
                                }}
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
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatMoney(invoice.paid) }}
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
                                        ? formatMoney(invoice.remaining)
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
                                    >لا ينطبق</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="FileText"
                title="التاجر ده لسه مالوش فواتير"
            />
        </SectionCard>
    </div>
</template>
