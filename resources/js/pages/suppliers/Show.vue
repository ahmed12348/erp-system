<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import DownloadMenu from '@/components/DownloadMenu.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatDateShort, formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    supplier: {
        id: number;
        name: string;
        country: string | null;
        phone: string | null;
        currency: string;
        openingBalance: number;
        notes: string | null;
    };
    summary: {
        purchases: number;
        returns: number;
        paid: number;
        balance: number;
        purchasesEgp: number;
        invoiceCount: number;
    };
    statement: {
        opening: number;
        rows: {
            id: number;
            kind: string;
            date: string;
            reference: string;
            description: string;
            debit: number;
            credit: number;
            balance: number;
            link: string | null;
        }[];
        closing: number;
    };
    invoices: {
        id: number;
        number: string;
        date: string;
        currency: string;
        exchangeRate: number;
        total: number;
        remaining: number;
    }[];
    openInvoices: { id: number; label: string; exchangeRate: number }[];
    filters: { search: string | null };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const open = ref(false);

/** بحث برقم فاتورة الشراء جوه الكشف */
const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(
        `/suppliers/${props.supplier.id}`,
        { search: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watchDebounced(search, applySearch, { debounce: 350 });

// نفس البحث بيمشي مع التحميل عشان الملف يطابق اللي على الشاشة
const statementQuery = computed(() =>
    search.value ? `search=${encodeURIComponent(search.value)}` : '',
);

const form = useForm({
    amount: '' as string | number,
    method: 'bank',
    date: new Date().toISOString().slice(0, 10),
    exchange_rate: props.invoices[0]?.exchangeRate ?? 1,
    reference: '',
    notes: '',
    invoice_id: '' as string | number,
});

function submit() {
    form.transform((data) => ({
        ...data,
        invoice_id: data.invoice_id === '' ? null : data.invoice_id,
    })).post(`/suppliers/${props.supplier.id}/payments`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.date = new Date().toISOString().slice(0, 10);
            open.value = false;
        },
    });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head :title="supplier.name" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="supplier.name"
            :description="`${supplier.country ?? ''} · التعامل بالـ ${supplier.currency}`"
        >
            <template #actions>
                <DownloadMenu
                    :href="`/exports/suppliers/${supplier.id}?${statementQuery}`"
                    label="تحميل الكشف"
                />
                <Dialog v-model:open="open">
                    <Button
                        data-test="open-supplier-payment"
                        @click="open = true"
                    >
                        تسجيل دفعة للمورد
                    </Button>

                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle
                                >دفعة إلى {{ supplier.name }}</DialogTitle
                            >
                            <DialogDescription>
                                المستحق حالياً
                                {{
                                    formatMoney(
                                        summary.balance,
                                        supplier.currency,
                                        2,
                                    )
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div class="space-y-2">
                                <Label for="amount">
                                    المبلغ ({{ supplier.currency }})
                                </Label>
                                <Input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    dir="ltr"
                                    class="text-start"
                                    data-test="supplier-payment-amount"
                                />
                                <InputError :message="form.errors.amount" />
                            </div>

                            <div class="space-y-2">
                                <Label for="exchange_rate"
                                    >سعر الصرف للجنيه</Label
                                >
                                <Input
                                    id="exchange_rate"
                                    v-model="form.exchange_rate"
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    dir="ltr"
                                    class="text-start"
                                />
                                <InputError
                                    :message="form.errors.exchange_rate"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="method">طريقة الدفع</Label>
                                <select
                                    id="method"
                                    v-model="form.method"
                                    :class="SELECT_CLASS"
                                >
                                    <option value="bank">تحويل بنكي</option>
                                    <option value="cash">نقدي</option>
                                    <option value="check">شيك</option>
                                </select>
                                <InputError :message="form.errors.method" />
                            </div>

                            <div class="space-y-2">
                                <Label for="date">التاريخ</Label>
                                <Input
                                    id="date"
                                    v-model="form.date"
                                    type="date"
                                    required
                                />
                                <InputError :message="form.errors.date" />
                            </div>

                            <div v-if="openInvoices.length" class="space-y-2">
                                <Label for="invoice_id"
                                    >خصم من فاتورة (اختياري)</Label
                                >
                                <select
                                    id="invoice_id"
                                    v-model="form.invoice_id"
                                    :class="SELECT_CLASS"
                                >
                                    <option value="">
                                        دفعة عامة على الحساب
                                    </option>
                                    <option
                                        v-for="invoice in openInvoices"
                                        :key="invoice.id"
                                        :value="invoice.id"
                                    >
                                        {{ invoice.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.invoice_id" />
                            </div>

                            <div class="space-y-2">
                                <Label for="reference"
                                    >رقم التحويل / الشيك</Label
                                >
                                <Input
                                    id="reference"
                                    v-model="form.reference"
                                    dir="ltr"
                                    class="text-start"
                                />
                            </div>

                            <DialogFooter class="gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="open = false"
                                >
                                    إلغاء
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    data-test="submit-supplier-payment"
                                >
                                    <Spinner v-if="form.processing" />
                                    حفظ الدفعة
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </template>
        </PageHeader>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">إجمالي المشتريات</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatMoney(summary.purchases, supplier.currency, 2) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatMoney(summary.purchasesEgp) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">المدفوع</p>
                <p class="mt-2 text-xl font-bold text-status-good tabular-nums">
                    {{ formatMoney(summary.paid, supplier.currency, 2) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">المستحق للمورد</p>
                <p
                    class="mt-2 text-xl font-bold tabular-nums"
                    data-test="supplier-balance"
                >
                    {{ formatMoney(summary.balance, supplier.currency, 2) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">عدد الفواتير</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatNumber(summary.invoiceCount) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    رصيد افتتاحي
                    {{
                        formatMoney(
                            supplier.openingBalance,
                            supplier.currency,
                            2,
                        )
                    }}
                </p>
            </div>
        </div>

        <SectionCard
            title="كشف حساب المورد"
            :description="`كل المبالغ بالـ ${supplier.currency}`"
            flush
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search
                            class="absolute start-2.5 top-2.5 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            class="h-9 w-48 ps-8"
                            placeholder="رقم الفاتورة"
                            aria-label="بحث برقم الفاتورة"
                            data-test="statement-search"
                        />
                    </div>
                    <Button
                        v-if="search"
                        variant="ghost"
                        size="sm"
                        @click="
                            search = '';
                            applySearch();
                        "
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
                                رقم الفاتورة
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
                                رصيد افتتاحي
                            </td>
                            <td class="px-5 py-2.5 font-semibold tabular-nums">
                                {{
                                    formatMoney(
                                        statement.opening,
                                        supplier.currency,
                                        2,
                                    )
                                }}
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
                            <td class="px-5 py-3">
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
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ row.reference }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-status-good tabular-nums"
                            >
                                {{
                                    row.debit > 0
                                        ? formatMoney(
                                              row.debit,
                                              supplier.currency,
                                              2,
                                          )
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{
                                    row.credit > 0
                                        ? formatMoney(
                                              row.credit,
                                              supplier.currency,
                                              2,
                                          )
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{
                                    formatMoney(
                                        row.balance,
                                        supplier.currency,
                                        2,
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40">
                        <tr class="font-semibold">
                            <td colspan="5" class="px-5 py-3">
                                الرصيد الختامي
                            </td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                data-test="supplier-closing"
                            >
                                {{
                                    formatMoney(
                                        statement.closing,
                                        supplier.currency,
                                        2,
                                    )
                                }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </SectionCard>
    </div>
</template>
