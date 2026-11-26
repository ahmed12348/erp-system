<script setup lang="ts">
import type { CompanyHeader } from '@/components/print/PrintSheet.vue';
import PrintSheet from '@/components/print/PrintSheet.vue';
import { formatDate, formatQuantity } from '@/lib/format';

/*
 * ورقة الجرد بتتطبع و**خانة المعدود فاضية** لما تكون لسه مفتوحة: اللي
 * بيعد بيمشي على الرفوف وبيكتب بالقلم، وبعدين بيدخّل اللي كتبه.
 */
defineProps<{
    company: CompanyHeader;
    count: {
        number: string;
        date: string;
        warehouse: string | null;
        category: string | null;
        statusLabel: string;
        isEditable: boolean;
        notes: string | null;
        createdBy: string | null;
        approvedBy: string | null;
        items: {
            id: number;
            sku: string | null;
            name: string | null;
            unit: string | null;
            systemQuantity: number;
            countedQuantity: number | null;
            difference: number | null;
        }[];
    };
    printedAt: string;
}>();
</script>

<template>
    <PrintSheet
        :company="company"
        title="ورقة جرد"
        :subtitle="`رقم ${count.number} · ${count.statusLabel}`"
        :printed-at="printedAt"
    >
        <section class="mt-6 grid grid-cols-2 gap-6 text-sm">
            <div class="space-y-1">
                <p>
                    <span class="text-neutral-500">المخزن: </span>
                    <span class="font-semibold">
                        {{ count.warehouse ?? '—' }}
                    </span>
                </p>
                <p>
                    <span class="text-neutral-500">التصنيف: </span>
                    <span class="font-semibold">
                        {{ count.category ?? 'كل الأصناف' }}
                    </span>
                </p>
            </div>
            <div class="space-y-1">
                <p>
                    <span class="text-neutral-500">التاريخ: </span>
                    <span class="font-semibold tabular-nums">
                        {{ formatDate(count.date) }}
                    </span>
                </p>
                <p>
                    <span class="text-neutral-500">فتحها: </span>
                    <span class="font-semibold">
                        {{ count.createdBy ?? '—' }}
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
                        class="w-20 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الوحدة
                    </th>
                    <th
                        class="w-24 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        رصيد النظام
                    </th>
                    <th
                        class="w-24 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        المعدود
                    </th>
                    <th
                        class="w-24 border border-neutral-300 px-2 py-2 text-start font-semibold"
                    >
                        الفرق
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in count.items" :key="item.id">
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
                    <td class="border border-neutral-300 px-2 py-1.5">
                        {{ item.unit ?? '—' }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{ formatQuantity(item.systemQuantity) }}
                    </td>
                    <!-- بتتطبع فاضية وهي مفتوحة — مكان القلم -->
                    <td
                        class="h-7 border border-neutral-300 px-2 py-1.5 tabular-nums"
                    >
                        {{
                            item.countedQuantity === null
                                ? ''
                                : formatQuantity(item.countedQuantity)
                        }}
                    </td>
                    <td
                        class="border border-neutral-300 px-2 py-1.5 font-semibold tabular-nums"
                    >
                        {{
                            item.difference === null
                                ? ''
                                : formatQuantity(item.difference)
                        }}
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-if="count.notes" class="mt-3 text-[13px]">
            <span class="font-semibold">ملاحظات:</span> {{ count.notes }}
        </p>

        <section class="mt-14 grid grid-cols-3 gap-8 text-[13px]">
            <div>
                <p class="font-semibold">عدّ البضاعة</p>
                <p
                    class="mt-8 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    الاسم والتوقيع
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
                    {{ count.approvedBy ?? '—' }}
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
