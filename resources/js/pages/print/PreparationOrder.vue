<script setup lang="ts">
import PrintSheet from '@/components/print/PrintSheet.vue';
import { formatDate, formatQuantity } from '@/lib/format';

/*
 * ورقة تحضير A4.
 *
 * **ولا عمود سعر، ومتعمّد.** الورقة بتتطبع وبتروح لعامل المخزن — محدش
 * فيهم محتاج يعرف التاجر بيشتري بكام.
 */
type Item = {
    id: number;
    sku: string | null;
    name: string | null;
    unit: string | null;
    quantity: number;
    preparedQuantity: number | null;
};

defineProps<{
    company: string;
    order: {
        number: string;
        date: string;
        statusLabel: string;
        warehouse: string | null;
        notes: string | null;
        customer: {
            name: string;
            phone: string | null;
            city: string | null;
            address: string | null;
        } | null;
        items: Item[];
    };
    printedAt: string;
}>();
</script>

<template>
    <PrintSheet
        :company="company"
        title="طلب تحضير"
        :subtitle="`رقم ${order.number} · ${order.statusLabel}`"
        :printed-at="printedAt"
    >
        <!-- بيانات الطلب -->
        <section class="mt-6 grid grid-cols-2 gap-6 text-sm">
            <div>
                <p class="mb-1 text-xs font-semibold text-neutral-500">
                    التاجر
                </p>
                <p class="text-base font-bold">
                    {{ order.customer?.name ?? '—' }}
                </p>
                <p class="mt-0.5 text-neutral-600">
                    {{ order.customer?.city ?? '—' }}
                    <template v-if="order.customer?.address">
                        — {{ order.customer.address }}</template
                    >
                </p>
                <p v-if="order.customer?.phone" class="mt-0.5 text-neutral-600">
                    تليفون:
                    <span dir="ltr">{{ order.customer.phone }}</span>
                </p>
            </div>

            <div class="space-y-1 text-sm">
                <div
                    class="flex justify-between border-b border-neutral-200 pb-1"
                >
                    <span class="text-neutral-500">رقم الطلب</span>
                    <span class="font-semibold tabular-nums">
                        {{ order.number }}
                    </span>
                </div>
                <div
                    class="flex justify-between border-b border-neutral-200 pb-1"
                >
                    <span class="text-neutral-500">التاريخ</span>
                    <span class="font-semibold tabular-nums">
                        {{ formatDate(order.date) }}
                    </span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-neutral-500">المخزن</span>
                    <span class="font-semibold">
                        {{ order.warehouse ?? '—' }}
                    </span>
                </div>
            </div>
        </section>

        <!-- البنود -->
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
                        الكمية
                    </th>
                    <!-- بتتطبع فاضية — مكان علامة القلم -->
                    <th
                        class="w-16 border border-neutral-300 px-2 py-2 text-center font-semibold"
                    >
                        تم
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in order.items" :key="item.id">
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
                        class="border border-neutral-300 px-2 py-1.5 font-semibold tabular-nums"
                    >
                        {{
                            formatQuantity(
                                item.preparedQuantity ?? item.quantity,
                            )
                        }}
                    </td>
                    <!-- مربع فاضي عشان اللي بيجمّع يعلّم فيه بالقلم -->
                    <td class="border border-neutral-300 p-1.5 text-center">
                        <span
                            class="inline-block size-4 border border-neutral-500"
                        ></span>
                    </td>
                </tr>

                <tr v-if="!order.items.length">
                    <td
                        colspan="6"
                        class="border border-neutral-300 px-2 py-6 text-center text-neutral-500"
                    >
                        مفيش أصناف في الطلب
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-if="order.notes" class="mt-4 text-[13px]">
            <span class="font-semibold">ملاحظات:</span> {{ order.notes }}
        </p>

        <!-- التوقيعات -->
        <section class="mt-14 grid grid-cols-2 gap-10 text-[13px]">
            <div>
                <p class="font-semibold">حضّر البضاعة</p>
                <p
                    class="mt-8 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    الاسم والتوقيع
                </p>
            </div>
            <div>
                <p class="font-semibold">استلم</p>
                <p
                    class="mt-8 border-t border-neutral-400 pt-1 text-neutral-500"
                >
                    الاسم والتوقيع
                </p>
            </div>
        </section>
    </PrintSheet>
</template>
