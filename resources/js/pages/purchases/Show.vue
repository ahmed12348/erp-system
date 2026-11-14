<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { AttachmentItem } from '@/components/AttachmentPanel.vue';
import AttachmentPanel from '@/components/AttachmentPanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import {
    formatDate,
    formatDateShort,
    formatMoney,
    formatNumber,
    formatQuantity,
} from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    invoice: {
        id: number;
        number: string;
        date: string;
        dueDate: string | null;
        currency: string;
        exchangeRate: number;
        subtotal: number;
        discount: number;
        shipping: number;
        customs: number;
        clearance: number;
        total: number;
        totalEgp: number;
        landedTotal: number;
        paid: number;
        remaining: number;
        notes: string | null;
        createdBy: string | null;
        warehouse: string | null;
        supplier: {
            id: number;
            name: string;
            country: string | null;
            phone: string | null;
            currency: string;
        } | null;
        items: {
            id: number;
            sku: string | null;
            name: string | null;
            unit: string | null;
            quantity: number;
            unitPrice: number;
            total: number;
            landedUnitCost: number;
        }[];
        payments: {
            id: number;
            date: string;
            amount: number;
            currency: string;
            method: string;
            reference: string | null;
        }[];
        attachments: AttachmentItem[];
    };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

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
            :description="`فاتورة شراء — ${formatDate(invoice.date)}`"
        />

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="بيانات المورد">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">المورد</dt>
                        <dd class="font-medium">
                            <Link
                                v-if="invoice.supplier"
                                :href="`/suppliers/${invoice.supplier.id}`"
                                class="hover:text-primary hover:underline"
                            >
                                {{ invoice.supplier.name }}
                            </Link>
                            <span v-else>—</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">بلد الاستيراد</dt>
                        <dd class="font-medium">
                            {{ invoice.supplier?.country ?? '—' }}
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
                        <dt class="text-muted-foreground">أنشأها</dt>
                        <dd class="font-medium">
                            {{ invoice.createdBy ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="العملة والتحويل">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">عملة الفاتورة</dt>
                        <dd class="font-medium">{{ invoice.currency }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">سعر الصرف</dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatNumber(invoice.exchangeRate, 4) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">
                            الإجمالي بعملة الفاتورة
                        </dt>
                        <dd class="font-semibold tabular-nums">
                            {{
                                formatMoney(invoice.total, invoice.currency, 2)
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-border pt-2"
                    >
                        <dt class="font-medium">الإجمالي بالجنيه</dt>
                        <dd
                            class="font-bold tabular-nums"
                            data-test="purchase-egp"
                        >
                            {{ formatMoney(invoice.totalEgp) }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="مصاريف الاستيراد">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">الشحن</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(invoice.shipping) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">الجمارك</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(invoice.customs) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">التخليص</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(invoice.clearance) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-border pt-2 font-semibold"
                    >
                        <dt>إجمالي المصاريف</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(invoice.landedTotal) }}
                        </dd>
                    </div>
                    <div class="flex justify-between text-base font-bold">
                        <dt>التكلفة الكلية الواصلة</dt>
                        <dd class="tabular-nums" data-test="purchase-landed">
                            {{
                                formatMoney(
                                    invoice.totalEgp + invoice.landedTotal,
                                )
                            }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>
        </div>

        <SectionCard
            title="بنود الفاتورة"
            description="التكلفة الواصلة بالجنيه شاملة نصيب البند من مصاريف الاستيراد"
            flush
        >
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
                                السعر ({{ invoice.currency }})
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التكلفة الواصلة للوحدة
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
                                {{
                                    formatMoney(
                                        item.unitPrice,
                                        invoice.currency,
                                        2,
                                    )
                                }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{
                                    formatMoney(item.total, invoice.currency, 2)
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-chart-1 tabular-nums"
                            >
                                {{ formatMoney(item.landedUnitCost) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>

        <SectionCard
            v-if="invoice.payments.length"
            title="المدفوعات للمورد"
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
                            <td class="px-5 py-3 font-semibold tabular-nums">
                                {{
                                    formatMoney(
                                        payment.amount,
                                        payment.currency,
                                        2,
                                    )
                                }}
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
