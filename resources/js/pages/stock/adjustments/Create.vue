<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type ProductOption = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    purchasePrice: number;
    stock: Record<string, number>;
};

const props = defineProps<{
    type: string;
    typeLabel: string;
    warehouses: { id: number; name: string }[];
    products: ProductOption[];
    reasons: { value: string; label: string }[];
    today: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const isDeduction = computed(() => props.type === 'deduction');

type Line = {
    product_id: number | '';
    quantity: number | string;
    unit_cost: number | string;
};

const form = useForm({
    type: props.type,
    warehouse_id: props.warehouses[0]?.id ?? ('' as number | ''),
    date: props.today,
    reason: '',
    notes: '',
    items: [{ product_id: '', quantity: 1, unit_cost: '' }] as Line[],
});

const productsById = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);

/** التكلفة الحالية هي الافتراضي، والمستخدم يقدر يعدّلها */
function onProductChange(line: Line) {
    const product = productsById.value.get(Number(line.product_id));

    if (product && line.unit_cost === '') {
        line.unit_cost = product.purchasePrice;
    }
}

/** المتاح في المخزن المختار — إذن الخصم مابينفعش يعدّيه */
function availableFor(productId: number | ''): number | null {
    const product = productsById.value.get(Number(productId));

    if (!product || !form.warehouse_id) {
        return null;
    }

    return product.stock[String(form.warehouse_id)] ?? 0;
}

function unitOf(productId: number | ''): string {
    return productsById.value.get(Number(productId))?.unit ?? '';
}

function addLine() {
    form.items.push({ product_id: '', quantity: 1, unit_cost: '' });
}

function removeLine(index: number) {
    form.items.splice(index, 1);

    if (form.items.length === 0) {
        addLine();
    }
}

function lineValue(line: Line): number {
    return Number(line.quantity || 0) * Number(line.unit_cost || 0);
}

const total = computed(() =>
    form.items.reduce((sum, line) => sum + lineValue(line), 0),
);

/** سطور الخصم اللي عدّت المتاح — السيرفر بيرفضها وقت الاعتماد */
const overLines = computed(() =>
    isDeduction.value
        ? form.items.filter((line) => {
              const available = availableFor(line.product_id);

              return (
                  available !== null && Number(line.quantity || 0) > available
              );
          })
        : [],
);

const canSubmit = computed(
    () =>
        form.reason !== '' &&
        form.items.some((line) => line.product_id !== '') &&
        overLines.value.length === 0,
);

function submit() {
    form.transform((data) => ({
        ...data,
        items: data.items.filter((line) => line.product_id !== ''),
    })).post('/stock-adjustments', { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head :title="`${typeLabel} جديد`" />

    <form
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            :title="`${typeLabel} جديد`"
            description="بيتحفظ مسودة — المخزون مابيتحركش غير بعد الاعتماد"
        />

        <SectionCard title="بيانات الإذن">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="warehouse_id">المخزن</Label>
                    <select
                        id="warehouse_id"
                        v-model="form.warehouse_id"
                        :class="SELECT_CLASS"
                        data-test="adjustment-warehouse"
                    >
                        <option
                            v-for="w in warehouses"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.warehouse_id" />
                </div>

                <div class="space-y-2">
                    <Label for="date">التاريخ</Label>
                    <Input
                        id="date"
                        v-model="form.date"
                        type="date"
                        dir="ltr"
                        class="text-start"
                        data-test="adjustment-date"
                    />
                    <InputError :message="form.errors.date" />
                </div>

                <div class="space-y-2">
                    <Label for="reason">السبب</Label>
                    <select
                        id="reason"
                        v-model="form.reason"
                        :class="SELECT_CLASS"
                        data-test="adjustment-reason"
                    >
                        <option value="" disabled>اختار السبب</option>
                        <option
                            v-for="r in reasons"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.reason" />
                </div>
            </div>
        </SectionCard>

        <SectionCard title="الأصناف" flush>
            <template #actions>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    data-test="add-line"
                    @click="addLine"
                >
                    <Plus class="size-4" />
                    سطر جديد
                </Button>
            </template>

            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-3 py-2.5 text-start font-medium">
                                الصنف
                            </th>
                            <th class="w-32 px-3 py-2.5 text-start font-medium">
                                الكمية
                            </th>
                            <th class="w-20 px-3 py-2.5 text-start font-medium">
                                الوحدة
                            </th>
                            <th class="w-36 px-3 py-2.5 text-start font-medium">
                                التكلفة
                            </th>
                            <th class="w-32 px-3 py-2.5 text-start font-medium">
                                القيمة
                            </th>
                            <th class="w-14 px-3 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="(line, index) in form.items" :key="index">
                            <td class="px-3 py-2">
                                <select
                                    v-model="line.product_id"
                                    :class="SELECT_CLASS"
                                    :data-test="`line-product-${index}`"
                                    @change="onProductChange(line)"
                                >
                                    <option value="" disabled>
                                        اختار الصنف
                                    </option>
                                    <option
                                        v-for="p in products"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.name }} ({{ p.sku }})
                                    </option>
                                </select>
                                <p
                                    v-if="
                                        isDeduction &&
                                        availableFor(line.product_id) !== null
                                    "
                                    class="mt-1 text-xs"
                                    :class="
                                        Number(line.quantity || 0) >
                                        availableFor(line.product_id)!
                                            ? 'font-semibold text-status-critical'
                                            : 'text-muted-foreground'
                                    "
                                >
                                    المتاح:
                                    {{
                                        formatQuantity(
                                            availableFor(line.product_id)!,
                                        )
                                    }}
                                </p>
                            </td>
                            <td class="px-3 py-2">
                                <Input
                                    v-model="line.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    dir="ltr"
                                    class="text-start"
                                    :data-test="`line-qty-${index}`"
                                />
                            </td>
                            <td class="px-3 py-2 text-xs text-muted-foreground">
                                {{ unitOf(line.product_id) || '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <Input
                                    v-model="line.unit_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    dir="ltr"
                                    class="text-start"
                                    :data-test="`line-cost-${index}`"
                                />
                            </td>
                            <td class="px-3 py-2 tabular-nums">
                                {{ formatMoney(lineValue(line)) }}
                            </td>
                            <td class="px-3 py-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    @click="removeLine(index)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40">
                        <tr class="font-bold">
                            <td class="px-3 py-3" colspan="4">
                                إجمالي قيمة الإذن
                            </td>
                            <td
                                class="px-3 py-3 tabular-nums"
                                data-test="adjustment-total"
                            >
                                {{ formatMoney(total) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div
                v-if="overLines.length"
                class="border-t border-border px-5 py-3 text-sm font-semibold text-status-critical"
                data-test="over-available"
            >
                فيه كميات أكبر من المتاح — الاعتماد هيترفض.
            </div>

            <InputError class="px-5 py-2" :message="form.errors.items" />
        </SectionCard>

        <SectionCard title="ملاحظات">
            <textarea
                v-model="form.notes"
                rows="2"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                data-test="adjustment-notes"
            ></textarea>
        </SectionCard>

        <div class="flex justify-end">
            <Button
                type="submit"
                :disabled="form.processing || !canSubmit"
                data-test="save-adjustment"
            >
                <Spinner v-if="form.processing" class="size-4" />
                حفظ كمسودة
            </Button>
        </div>
    </form>
</template>
