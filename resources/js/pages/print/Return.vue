<script setup lang="ts">
import PrintSheet from '@/components/print/PrintSheet.vue';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';

const props = defineProps<{
    company: string;
    doc: {
        number: string;
        type: string;
        date: string;
        currency: string;
        subtotal: number;
        tax: number;
        total: number;
        reason: string | null;
        notes: string | null;
        isOrphan: boolean;
        etaUuid: string | null;
        warehouse: string | null;
        party: string | null;
        original: { number: string; date: string } | null;
        items: {
            id: number;
            sku: string | null;
            name: string | null;
            unit: string | null;
            quantity: number;
            unitPrice: number;
            total: number;
        }[];
        refunds: { id: number; amount: number; method: string }[];
    };
    printedAt: string;
}>();

const isSaleReturn = props.doc.type === 'sale_return';

/*
 * المرتجع بيتطبع كـ«إشعار خصم» — ده اسمه في منظومة الفاتورة
 * الإلكترونية، واللي بيستلمه محتاج يعرف إنه ده مش فاتورة تانية.
 */
const title = isSaleReturn ? 'إشعار خصم — مرتجع مبيعات' : 'مرتجع مشتريات';
</script>

<template>
    <PrintSheet
        :company="company"
        :title="title"
        :subtitle="`رقم ${doc.number}`"
        :printed-at="printedAt"
    >
        <section class="mt-6 grid grid-cols-2 gap-6 text-sm">
            <div>
                <p class="mb-1 text-xs font-semibold text-neutral-500">
                    {{ isSaleReturn ? 'التاجر' : 'المورد' }}
                </p>
                <p class="text-base font-bold">{{ doc.party ?? '—' }}</p>
                <p class="mt-0.5 text-neutral-600">
                    المخزن: {{ doc.warehouse ?? '—' }}
                </p>
            </div>

            <div class="space-y-1 text-sm">
                <div
                    class="flex justify-between border-b border-neutral-200 pb-1"
                >
                    <span class="text-neutral-500">تاريخ المرتجع</span>
                    <span class="font-semibold tabular-nums">
                        {{ formatDate(doc.date) }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-b border-neutral-200 pb-1"
                >
                    <span class="text-neutral-500">الفاتورة الأصلية</span>
                    <span class="font-semibold tabular-nums">
                        {{ doc.original?.number ?? 'من غير فاتورة' }}
                    </span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-neutral-500">السبب</span>
                    <span class="font-semibold">{{ doc.reason ?? '—' }}</span>
                </div>
            </div>
        </section>

        <!-- المرتجع اليتيم لازم يبان على الورقة كمان -->
        <p
            v-if="doc.isOrphan"
            class="mt-4 border border-neutral-400 px-3 py-2 text-[12px] font-semibold"
        >
            مستند مرتجع من غير فاتورة أصلية — اتسجّل بإذن الإدارة.
        </p>

        <table class="mt-7 w-full border-collapse text-[13px]">
            <thead>
                <tr class="bg-neutral-100 text-xs">
                    <th
                        class="w-10 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        م
                    </th>
                    <th
                        class="w-28 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الكود
                    </th>
                    <th
                        class="border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الصنف
                    </th>
                    <th
                        class="w-24 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الكمية
                    </th>
                    <th
                        class="w-28 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        السعر
                    </th>
                    <th
                        class="w-28 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الإجمالي
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in doc.items" :key="item.id">
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ index + 1 }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ item.sku ?? '—' }}
                    </td>
                    <td class="border border-neutral-300 px-2 py-1.5">
                        {{ item.name ?? '—' }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{
                            formatQuantity(
                                item.quantity,
                                item.unit ?? undefined,
                            )
                        }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ formatMoney(item.unitPrice, doc.currency) }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 font-semibold tabular-nums"
                    >
                        {{ formatMoney(item.total, doc.currency) }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td
                        class="border border-neutral-300 px-2 py-1.5"
                        colspan="5"
                    >
                        قيمة البنود
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ formatMoney(doc.subtotal, doc.currency) }}
                    </td>
                </tr>
                <tr v-if="doc.tax > 0">
                    <td
                        class="border border-neutral-300 px-2 py-1.5"
                        colspan="5"
                    >
                        الضريبة
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ formatMoney(doc.tax, doc.currency) }}
                    </td>
                </tr>
                <tr class="bg-neutral-100 font-bold">
                    <td class="border border-neutral-300 px-2 py-2" colspan="5">
                        إجمالي المرتجع
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-2 tabular-nums"
                    >
                        {{ formatMoney(doc.total, doc.currency) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <p v-if="doc.refunds.length" class="mt-4 text-[13px]">
            <span class="font-semibold">اترد نقدي:</span>
            <span
                v-for="refund in doc.refunds"
                :key="refund.id"
                class="tabular-nums"
            >
                {{ formatMoney(refund.amount, doc.currency) }}
                ({{ refund.method }})
            </span>
        </p>

        <p v-if="doc.notes" class="mt-2 text-[13px]">
            <span class="font-semibold">ملاحظات:</span> {{ doc.notes }}
        </p>

        <p
            v-if="doc.etaUuid"
            class="mt-3 text-[11px] text-neutral-500 tabular-nums"
        >
            رقم المنظومة: {{ doc.etaUuid }}
        </p>

        <section class="mt-14 grid grid-cols-2 gap-10 text-[13px]">
            <div>
                <p class="font-semibold">استلم البضاعة</p>
                <p
                    class="mt-8 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    الاسم والتوقيع
                </p>
            </div>
            <div>
                <p class="font-semibold">
                    {{ isSaleReturn ? 'سلّم البضاعة' : 'استلم المورد' }}
                </p>
                <p
                    class="mt-8 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    الاسم والتوقيع
                </p>
            </div>
        </section>
    </PrintSheet>
</template>
