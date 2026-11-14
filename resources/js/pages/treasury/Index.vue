<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Paperclip, Wallet } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import type { AttachmentItem } from '@/components/AttachmentPanel.vue';
import AttachmentPanel from '@/components/AttachmentPanel.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatDateShort, formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    date: string;
    type: string;
    party: string | null;
    partyLink: string | null;
    methodLabel: string;
    reference: string | null;
    invoice: string | null;
    amount: number;
    currency: string;
    exchangeRate: number;
    amountEgp: number;
    attachments: AttachmentItem[];
};

const props = defineProps<{
    rows: Row[];
    totals: { in: number; out: number; net: number };
    byMethod: {
        method: string;
        label: string;
        in: number;
        out: number;
        count: number;
    }[];
    filters: Record<string, string | undefined>;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    type: props.filters.type ?? '',
    method: props.filters.method ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/treasury',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';

/*
 * بنمسك رقم السند مش الصف نفسه، عشان بعد رفع أو حذف مرفق الصفحة بتتحدّث
 * وقائمة المرفقات في الحوار تفضل متزامنة مع البيانات الجديدة.
 */
const openPaymentId = ref<number | null>(null);

const openRow = computed(
    () => props.rows.find((row) => row.id === openPaymentId.value) ?? null,
);

function openAttachments(row: Row) {
    openPaymentId.value = row.id;
}
</script>

<template>
    <Head title="الخزينة" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="الخزينة"
            description="التحصيلات من التجار والمدفوعات للموردين — كل الإجماليات بالجنيه المصري"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">الداخل</p>
                <p
                    class="mt-2 text-xl font-bold text-status-good tabular-nums"
                    data-test="treasury-in"
                >
                    {{ formatMoney(totals.in) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">الخارج</p>
                <p
                    class="mt-2 text-xl font-bold text-status-critical tabular-nums"
                    data-test="treasury-out"
                >
                    {{ formatMoney(totals.out) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">الصافي</p>
                <p
                    class="mt-2 text-xl font-bold tabular-nums"
                    :class="
                        totals.net >= 0
                            ? 'text-foreground'
                            : 'text-status-critical'
                    "
                    data-test="treasury-net"
                >
                    {{ formatMoney(totals.net) }}
                </p>
            </div>
        </div>

        <SectionCard title="حسب طريقة الدفع">
            <div class="grid gap-4 sm:grid-cols-3">
                <div
                    v-for="method in byMethod"
                    :key="method.method"
                    class="rounded-lg border border-border p-4"
                >
                    <p class="text-sm font-medium">{{ method.label }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ formatNumber(method.count) }} سند
                    </p>
                    <dl class="mt-2 space-y-1 text-xs">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">داخل</dt>
                            <dd
                                class="font-medium text-status-good tabular-nums"
                            >
                                {{ formatMoney(method.in) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">خارج</dt>
                            <dd
                                class="font-medium text-status-critical tabular-nums"
                            >
                                {{ formatMoney(method.out) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </SectionCard>

        <SectionCard title="حركة الخزينة" flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="filters.type"
                        :class="SELECT_CLASS"
                        data-test="treasury-type"
                        @change="apply"
                    >
                        <option value="">داخل وخارج</option>
                        <option value="receipt">تحصيلات بس</option>
                        <option value="disbursement">مدفوعات بس</option>
                    </select>

                    <select
                        v-model="filters.method"
                        :class="SELECT_CLASS"
                        @change="apply"
                    >
                        <option value="">كل الطرق</option>
                        <option value="cash">نقدي</option>
                        <option value="bank">تحويل بنكي</option>
                        <option value="check">شيك</option>
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

            <div v-if="rows.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                النوع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الطرف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الطريقة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المرجع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المبلغ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                بالجنيه
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                مرفقات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.type === 'receipt'
                                            ? 'bg-status-good/10 text-status-good'
                                            : 'bg-status-critical/10 text-status-critical'
                                    "
                                >
                                    {{
                                        row.type === 'receipt' ? 'تحصيل' : 'دفع'
                                    }}
                                </span>
                            </td>
                            <td class="max-w-48 truncate px-5 py-3">
                                <Link
                                    v-if="row.partyLink"
                                    :href="row.partyLink"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ row.party }}
                                </Link>
                                <span v-else>{{ row.party ?? '—' }}</span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ row.methodLabel }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ row.reference ?? row.invoice ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.amount, row.currency, 2) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                                :class="
                                    row.type === 'receipt'
                                        ? 'text-status-good'
                                        : 'text-status-critical'
                                "
                            >
                                <span dir="ltr">
                                    {{ row.type === 'receipt' ? '+' : '−'
                                    }}{{ formatMoney(row.amountEgp) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs transition-colors hover:bg-accent"
                                    :class="
                                        row.attachments.length
                                            ? 'text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    :aria-label="`مرفقات السند رقم ${row.id}`"
                                    @click="openAttachments(row)"
                                >
                                    <Paperclip class="size-3.5" />
                                    <span class="tabular-nums">
                                        {{
                                            formatNumber(row.attachments.length)
                                        }}
                                    </span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="Wallet"
                title="مفيش حركة خزينة في الفترة دي"
            />
        </SectionCard>

        <Dialog :open="openRow !== null" @update:open="openPaymentId = null">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        مرفقات سند
                        {{ openRow?.type === 'receipt' ? 'التحصيل' : 'الدفع' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ openRow?.party ?? '—' }} ·
                        {{ formatDateShort(openRow?.date) }} ·
                        {{
                            formatMoney(
                                openRow?.amount ?? 0,
                                openRow?.currency,
                                2,
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <AttachmentPanel
                    v-if="openRow"
                    :attachments="openRow.attachments"
                    :upload-url="`/payments/${openRow.id}/attachments`"
                    :heading="null"
                    :subheading="null"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
