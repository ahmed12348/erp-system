<script setup lang="ts">
import type { CompanyHeader } from '@/components/print/PrintSheet.vue';
import PrintSheet from '@/components/print/PrintSheet.vue';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';

defineProps<{
    company: CompanyHeader;
    adjustment: {
        number: string;
        typeLabel: string;
        date: string;
        warehouse: string | null;
        reason: string;
        isApproved: boolean;
        value: number;
        notes: string | null;
        createdBy: string | null;
        approvedBy: string | null;
        items: {
            id: number;
            sku: string | null;
            name: string | null;
            unit: string | null;
            quantity: number;
            unitCost: number;
            value: number;
        }[];
    };
    printedAt: string;
}>();
</script>

<template>
    <PrintSheet
        :company="company"
        :title="adjustment.typeLabel"
        :subtitle="`رقم ${adjustment.number}`"
        :printed-at="printedAt"
    >
        <section class="mt-6 grid grid-cols-2 gap-6 text-sm">
            <div class="space-y-1">
                <p>
                    <span class="text-neutral-500">المخزن: </span>
                    <span class="font-semibold">
                        {{ adjustment.warehouse ?? '—' }}
                    </span>
                </p>
                <p>
                    <span class="text-neutral-500">السبب: </span>
                    <span class="font-semibold">{{ adjustment.reason }}</span>
                </p>
            </div>
            <div class="space-y-1">
                <p>
                    <span class="text-neutral-500">التاريخ: </span>
                    <span class="font-semibold tabular-nums">
                        {{ formatDate(adjustment.date) }}
                    </span>
                </p>
                <p>
                    <span class="text-neutral-500">الحالة: </span>
                    <span class="font-semibold">
                        {{
                            adjustment.isApproved
                                ? 'معتمد'
                                : 'مسودة — المخزون مااتحركش'
                        }}
                    </span>
                </p>
            </div>
        </section>

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
                        التكلفة
                    </th>
                    <th
                        class="w-28 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        القيمة
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in adjustment.items" :key="item.id">
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
                        {{ formatMoney(item.unitCost) }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 font-semibold tabular-nums"
                    >
                        {{ formatMoney(item.value) }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="bg-neutral-100 font-bold">
                    <td class="border border-neutral-300 px-2 py-2" colspan="5">
                        إجمالي القيمة بالتكلفة
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-2 tabular-nums"
                    >
                        {{ formatMoney(adjustment.value) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <p v-if="adjustment.notes" class="mt-3 text-[13px]">
            <span class="font-semibold">ملاحظات:</span> {{ adjustment.notes }}
        </p>

        <section class="mt-14 grid grid-cols-3 gap-8 text-[13px]">
            <div>
                <p class="font-semibold">كتب الإذن</p>
                <p class="mt-1 text-neutral-600">
                    {{ adjustment.createdBy ?? '—' }}
                </p>
                <p
                    class="mt-6 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    التوقيع
                </p>
            </div>
            <div>
                <p class="font-semibold">أمين المخزن</p>
                <p
                    class="mt-8 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    الاسم والتوقيع
                </p>
            </div>
            <div>
                <p class="font-semibold">الاعتماد</p>
                <p class="mt-1 text-neutral-600">
                    {{ adjustment.approvedBy ?? '—' }}
                </p>
                <p
                    class="mt-6 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    التوقيع
                </p>
            </div>
        </section>
    </PrintSheet>
</template>
