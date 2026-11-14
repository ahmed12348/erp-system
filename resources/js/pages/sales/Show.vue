<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Printer, Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import type { AttachmentItem } from '@/components/AttachmentPanel.vue';
import AttachmentPanel from '@/components/AttachmentPanel.vue';
import EtaBadge from '@/components/EtaBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    formatDate,
    formatDateShort,
    formatMoney,
    formatQuantity,
} from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Invoice = {
    id: number;
    number: string;
    type: string;
    date: string;
    dueDate: string | null;
    status: string;
    currency: string;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    paid: number;
    remaining: number;
    notes: string | null;
    etaStatus: string | null;
    etaUuid: string | null;
    createdBy: string | null;
    warehouse: string | null;
    customer: {
        id: number;
        name: string;
        phone: string | null;
        city: string | null;
        address: string | null;
    } | null;
    items: {
        id: number;
        sku: string | null;
        name: string | null;
        unit: string | null;
        quantity: number;
        unitPrice: number;
        discount: number;
        total: number;
    }[];
    payments: {
        id: number;
        date: string;
        amount: number;
        method: string;
        reference: string | null;
    }[];
    attachments: AttachmentItem[];
};

const props = defineProps<{
    invoice: Invoice;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const sending = ref(false);

/** الإرسال متاح لو الفاتورة بيع ولسه مااتبعتتش (أو اترفضت) */
const canSend = computed(
    () =>
        props.invoice.type === 'sale' &&
        [null, 'pending', 'rejected'].includes(props.invoice.etaStatus),
);

function sendToEta() {
    sending.value = true;

    router.post(
        `/sales/${props.invoice.id}/eta`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                sending.value = false;
            },
        },
    );
}

const METHOD_LABELS: Record<string, string> = {
    cash: 'نقدي',
    bank: 'تحويل بنكي',
    check: 'شيك',
};
</script>

<template>
    <Head :title="invoice.number" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="invoice.number"
            :description="`${invoice.type === 'sale' ? 'فاتورة بيع' : 'مرتجع مبيعات'} — ${formatDate(invoice.date)}`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <a :href="`/sales/${invoice.id}/print`" target="_blank">
                        <Printer class="size-4" />
                        طباعة
                    </a>
                </Button>
                <Button
                    v-if="canSend"
                    :disabled="sending"
                    data-test="send-eta"
                    @click="sendToEta"
                >
                    <Spinner v-if="sending" />
                    <Send v-else class="size-4" />
                    إرسال للفاتورة الإلكترونية
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="بيانات الفاتورة">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">التاجر</dt>
                        <dd class="font-medium">
                            <Link
                                v-if="invoice.customer"
                                :href="`/customers/${invoice.customer.id}`"
                                class="hover:text-primary hover:underline"
                            >
                                {{ invoice.customer.name }}
                            </Link>
                            <span v-else>—</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">المخزن</dt>
                        <dd class="font-medium">
                            {{ invoice.warehouse ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">التاريخ</dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatDateShort(invoice.date) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">الاستحقاق</dt>
                        <dd class="font-medium tabular-nums">
                            {{
                                invoice.dueDate
                                    ? formatDateShort(invoice.dueDate)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">أنشأها</dt>
                        <dd class="font-medium">
                            {{ invoice.createdBy ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="الفاتورة الإلكترونية">
                <div class="space-y-3 text-sm">
                    <EtaBadge :status="invoice.etaStatus" />
                    <p
                        v-if="invoice.etaUuid"
                        class="text-xs break-all text-muted-foreground"
                    >
                        الرقم المرجعي:
                        <span dir="ltr" class="font-mono">{{
                            invoice.etaUuid
                        }}</span>
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        الفاتورة لسه مااتبعتتش لمنظومة مصلحة الضرائب.
                    </p>
                    <p
                        class="rounded-md bg-muted px-3 py-2 text-xs text-muted-foreground"
                    >
                        ملاحظة: الربط بالمنظومة محاكاة للعرض التوضيحي — مفيش
                        إرسال فعلي.
                    </p>
                </div>
            </SectionCard>

            <SectionCard title="السداد">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">إجمالي الفاتورة</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ formatMoney(invoice.total) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">المدفوع</dt>
                        <dd class="font-semibold text-status-good tabular-nums">
                            {{ formatMoney(invoice.paid) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-border pt-2"
                    >
                        <dt class="font-medium">المتبقي</dt>
                        <dd
                            class="text-base font-bold tabular-nums"
                            :class="
                                invoice.remaining > 0
                                    ? 'text-status-critical'
                                    : 'text-status-good'
                            "
                        >
                            {{ formatMoney(invoice.remaining) }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>
        </div>

        <SectionCard title="بنود الفاتورة" flush>
            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الصنف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الكود
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الكمية
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                السعر
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الخصم
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in invoice.items" :key="item.id">
                            <td class="px-5 py-3 font-medium">
                                {{ item.name }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ item.sku }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatQuantity(item.quantity, item.unit) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(item.unitPrice) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{
                                    item.discount > 0
                                        ? formatMoney(item.discount)
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(item.total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40 text-sm">
                        <tr>
                            <td
                                colspan="5"
                                class="px-5 py-2 text-muted-foreground"
                            >
                                إجمالي البنود
                            </td>
                            <td class="px-5 py-2 tabular-nums">
                                {{ formatMoney(invoice.subtotal) }}
                            </td>
                        </tr>
                        <tr v-if="invoice.discount > 0">
                            <td
                                colspan="5"
                                class="px-5 py-2 text-muted-foreground"
                            >
                                خصم على الفاتورة
                            </td>
                            <td class="px-5 py-2 tabular-nums">
                                − {{ formatMoney(invoice.discount) }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                colspan="5"
                                class="px-5 py-2 text-muted-foreground"
                            >
                                ضريبة القيمة المضافة
                            </td>
                            <td class="px-5 py-2 tabular-nums">
                                {{ formatMoney(invoice.tax) }}
                            </td>
                        </tr>
                        <tr class="text-base font-bold">
                            <td colspan="5" class="px-5 py-3">الإجمالي</td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                data-test="invoice-total"
                            >
                                {{ formatMoney(invoice.total) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </SectionCard>

        <SectionCard
            v-if="invoice.payments.length"
            title="التحصيلات على الفاتورة"
            flush
        >
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
                                الطريقة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المرجع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المبلغ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="payment in invoice.payments"
                            :key="payment.id"
                        >
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatDateShort(payment.date) }}
                            </td>
                            <td class="px-5 py-3">
                                {{
                                    METHOD_LABELS[payment.method] ??
                                    payment.method
                                }}
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ payment.reference ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold text-status-good tabular-nums"
                            >
                                {{ formatMoney(payment.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>

        <AttachmentPanel
            :attachments="invoice.attachments"
            :upload-url="`/invoices/${invoice.id}/attachments`"
        />

        <SectionCard v-if="invoice.notes" title="ملاحظات">
            <p class="text-sm text-muted-foreground">{{ invoice.notes }}</p>
        </SectionCard>
    </div>
</template>
