<script setup lang="ts">
import PrintSheet from '@/components/print/PrintSheet.vue';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';

defineProps<{
    company: string;
    invoice: {
        number: string;
        type: string;
        date: string;
        dueDate: string | null;
        subtotal: number;
        discount: number;
        tax: number;
        total: number;
        paid: number;
        remaining: number;
        notes: string | null;
        etaUuid: string | null;
        warehouse: string | null;
        customer: {
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
    };
    printedAt: string;
}>();
</script>

<template>
    <PrintSheet
        :company="company"
        :title="invoice.type === 'sale' ? 'فاتورة بيع' : 'مرتجع مبيعات'"
        :subtitle="`رقم ${invoice.number}`"
        :printed-at="printedAt"
    >
        <section class="mt-6 grid grid-cols-2 gap-6 text-sm">
            <div>
                <p class="mb-1 text-xs font-semibold text-neutral-500">
                    فاتورة إلى
                </p>
                <p class="text-base font-bold">
                    {{ invoice.customer?.name ?? '—' }}
                </p>
                <p class="mt-0.5 text-neutral-600">
                    {{ invoice.customer?.city ?? '—' }}
                    <template v-if="invoice.customer?.address">
                        — {{ invoice.customer.address }}
                    </template>
                </p>
                <p
                    v-if="invoice.customer?.phone"
                    class="mt-0.5 text-neutral-600"
                >
                    تليفون: <span dir="ltr">{{ invoice.customer.phone }}</span>
                </p>
            </div>

            <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-neutral-500">تاريخ الفاتورة</span>
                    <span class="font-semibold">{{
                        formatDate(invoice.date)
                    }}</span>
                </div>
                <div v-if="invoice.dueDate" class="flex justify-between">
                    <span class="text-neutral-500">تاريخ الاستحقاق</span>
                    <span class="font-semibold">{{
                        formatDate(invoice.dueDate)
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-neutral-500">المخزن</span>
                    <span class="font-semibold">{{
                        invoice.warehouse ?? '—'
                    }}</span>
                </div>
                <div v-if="invoice.etaUuid" class="flex justify-between gap-2">
                    <span class="shrink-0 text-neutral-500"
                        >المرجع الإلكتروني</span
                    >
                    <span dir="ltr" class="truncate font-mono text-[11px]">
                        {{ invoice.etaUuid }}
                    </span>
                </div>
            </div>
        </section>

        <table class="mt-7 w-full border-collapse text-[13px]">
            <thead>
                <tr class="bg-neutral-100 text-xs">
                    <th
                        class="w-8 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        م
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الصنف
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الكود
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الكمية
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        السعر
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الخصم
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الإجمالي
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, i) in invoice.items" :key="item.id">
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ i + 1 }}
                    </td>
                    <td class="border border-neutral-300 px-2 py-1.5">
                        {{ item.name }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ item.sku }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums"
                    >
                        {{ formatQuantity(item.quantity, item.unit) }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums"
                    >
                        {{ formatMoney(item.unitPrice) }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums"
                    >
                        {{
                            item.discount > 0 ? formatMoney(item.discount) : '—'
                        }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 font-semibold whitespace-nowrap tabular-nums"
                    >
                        {{ formatMoney(item.total) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <section class="mt-5 flex justify-start">
            <dl class="w-72 space-y-1 text-sm">
                <div class="flex justify-between">
                    <dt class="text-neutral-500">إجمالي البنود</dt>
                    <dd class="tabular-nums">
                        {{ formatMoney(invoice.subtotal) }}
                    </dd>
                </div>
                <div v-if="invoice.discount > 0" class="flex justify-between">
                    <dt class="text-neutral-500">خصم</dt>
                    <dd class="tabular-nums">
                        − {{ formatMoney(invoice.discount) }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-neutral-500">ضريبة القيمة المضافة</dt>
                    <dd class="tabular-nums">{{ formatMoney(invoice.tax) }}</dd>
                </div>
                <div
                    class="flex justify-between border-t-2 border-neutral-800 pt-1.5 text-base font-bold"
                >
                    <dt>الإجمالي</dt>
                    <dd class="tabular-nums">
                        {{ formatMoney(invoice.total) }}
                    </dd>
                </div>
                <div class="flex justify-between pt-1">
                    <dt class="text-neutral-500">المدفوع</dt>
                    <dd class="tabular-nums">
                        {{ formatMoney(invoice.paid) }}
                    </dd>
                </div>
                <div class="flex justify-between font-semibold">
                    <dt>المتبقي</dt>
                    <dd class="tabular-nums">
                        {{ formatMoney(invoice.remaining) }}
                    </dd>
                </div>
            </dl>
        </section>

        <p v-if="invoice.notes" class="mt-5 text-xs text-neutral-600">
            ملاحظات: {{ invoice.notes }}
        </p>

        <section class="mt-12 grid grid-cols-2 gap-10 text-xs text-neutral-600">
            <div class="border-t border-neutral-400 pt-2 text-center">
                توقيع المسؤول
            </div>
            <div class="border-t border-neutral-400 pt-2 text-center">
                توقيع المستلم
            </div>
        </section>
    </PrintSheet>
</template>
