<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ClipboardList, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

/*
 * **مفيش سعر في الملف ده.** لا في النوع ولا في الفورم ولا في الجدول.
 * الورقة اللي بتطلع من هنا بتروح لعامل المخزن.
 *
 * والسيرفر بيشيل أعمدة الأسعار من الكتالوج قبل ما يبعته أصلاً — الحتة
 * دي مش معتمدة على إن الواجهة مش هتعرضها.
 */
type ProductOption = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    category: string | null;
    minStock: number;
    /** المتاح لكل مخزن = الرصيد ناقص المحجوز بطلبات التحضير */
    stock: Record<string, number>;
    /** المحجوز لوحده — بنقوله للمستخدم عشان يفهم ليه المتاح أقل من الرصيد */
    reserved: Record<string, number>;
};

const props = defineProps<{
    customers: { id: number; name: string; city: string | null }[];
    warehouses: { id: number; name: string }[];
    products: ProductOption[];
    today: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const missing = computed(() => {
    const items: string[] = [];

    if (!props.warehouses.length) {
        items.push('مخزن');
    }

    if (!props.customers.length) {
        items.push('تاجر');
    }

    if (!props.products.length) {
        items.push('صنف');
    }

    return items;
});

type Line = {
    product_id: number | '';
    quantity: number | string;
};

const form = useForm({
    customer_id: '' as number | '',
    warehouse_id: props.warehouses[0]?.id ?? ('' as number | ''),
    date: props.today,
    notes: '',
    items: [{ product_id: '', quantity: 1 }] as Line[],
});

const productsById = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);

/** المتاح من الصنف في المخزن المختار */
function availableOf(product: ProductOption): number {
    if (!form.warehouse_id) {
        return 0;
    }

    return product.stock[String(form.warehouse_id)] ?? 0;
}

function reservedOf(product: ProductOption): number {
    if (!form.warehouse_id) {
        return 0;
    }

    return product.reserved[String(form.warehouse_id)] ?? 0;
}

function availableFor(productId: number | ''): number | null {
    const product = productsById.value.get(Number(productId));

    return product ? availableOf(product) : null;
}

/**
 * الصنف اللي متاحه صفر بيتعرض ومتقفل، مش بيتشال.
 *
 * لو اتشال، اللي بيكتب الطلب بيفضل بيدوّر عليه وبيفتكر إن فيه غلط في
 * الكتالوج. ولما يبان ومتقفل، السبب واضح من أول نظرة.
 */
function isUnavailable(product: ProductOption): boolean {
    return availableOf(product) <= 0;
}

/** نص الخيار في القايمة — بيقول المتاح، والمحجوز لو فيه */
function optionLabel(product: ProductOption): string {
    const available = availableOf(product);
    const reserved = reservedOf(product);

    if (available <= 0) {
        return reserved > 0
            ? `${product.name} (${product.sku}) — متاح 0، محجوز ${formatQuantity(reserved)}`
            : `${product.name} (${product.sku}) — مفيش رصيد`;
    }

    return `${product.name} (${product.sku}) — متاح ${formatQuantity(available)} ${product.unit}`;
}

function unitOf(productId: number | ''): string {
    return productsById.value.get(Number(productId))?.unit ?? '';
}

function addLine() {
    form.items.push({ product_id: '', quantity: 1 });
}

function removeLine(index: number) {
    form.items.splice(index, 1);

    if (form.items.length === 0) {
        addLine();
    }
}

/**
 * السطور اللي الكمية فيها عدّت المتاح.
 *
 * الحقل نفسه فيه `max`، بس المتصفح مابيمنعش الكتابة بالإيد — فبنحذّر
 * وبنقفل الحفظ. السيرفر بيرفضها كمان؛ ده مجرد إننا نقول السبب قبل ما
 * الصفحة تروح وترجع.
 */
const overLines = computed(() =>
    form.items
        .map((line, index) => ({ line, index }))
        .filter(({ line }) => {
            const available = availableFor(line.product_id);

            return available !== null && Number(line.quantity || 0) > available;
        }),
);

const filledLines = computed(() =>
    form.items.filter((line) => line.product_id !== ''),
);

const canSubmit = computed(
    () => filledLines.value.length > 0 && overLines.value.length === 0,
);

function submit() {
    form.transform((data) => ({
        ...data,
        items: data.items.filter((line) => line.product_id !== ''),
    })).post('/preparation-orders', { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="طلب تحضير جديد" />

    <div v-if="missing.length" class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="طلب تحضير جديد"
            description="اختر التاجر والمخزن وأضف الأصناف"
        />
        <EmptyState
            :icon="ClipboardList"
            title="لسه ناقص بيانات أساسية"
            :description="`لازم الأول تسجّل: ${missing.join('، ')}.`"
        />
    </div>

    <form
        v-else
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="طلب تحضير جديد"
            description="ورقة للمخزن — كميات بس، من غير أسعار"
        />

        <SectionCard title="بيانات الطلب">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="customer_id">التاجر</Label>
                    <select
                        id="customer_id"
                        v-model="form.customer_id"
                        :class="SELECT_CLASS"
                        data-test="order-customer"
                    >
                        <option value="" disabled>اختر التاجر</option>
                        <option
                            v-for="c in customers"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name
                            }}<template v-if="c.city"> — {{ c.city }}</template>
                        </option>
                    </select>
                    <InputError :message="form.errors.customer_id" />
                </div>

                <div class="space-y-2">
                    <Label for="warehouse_id">المخزن</Label>
                    <select
                        id="warehouse_id"
                        v-model="form.warehouse_id"
                        :class="SELECT_CLASS"
                        data-test="order-warehouse"
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
                    <p class="text-xs text-muted-foreground">
                        المتاح بيتحسب من المخزن ده
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="date">التاريخ</Label>
                    <Input
                        id="date"
                        v-model="form.date"
                        type="date"
                        dir="ltr"
                        class="text-start"
                        data-test="order-date"
                    />
                    <InputError :message="form.errors.date" />
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
                            <th class="w-40 px-3 py-2.5 text-start font-medium">
                                الكمية
                            </th>
                            <th class="w-24 px-3 py-2.5 text-start font-medium">
                                الوحدة
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
                                >
                                    <option value="" disabled>
                                        اختر الصنف
                                    </option>
                                    <!--
                                        الصنف اللي متاحه صفر بيبان ومتقفل —
                                        عشان اللي بيكتب الطلب يعرف إنه موجود
                                        بس محجوز أو خلصان، مش يفتكر إن فيه
                                        غلط في الكتالوج
                                    -->
                                    <option
                                        v-for="p in products"
                                        :key="p.id"
                                        :value="p.id"
                                        :disabled="isUnavailable(p)"
                                        :data-test="
                                            isUnavailable(p)
                                                ? `product-unavailable-${p.id}`
                                                : `product-${p.id}`
                                        "
                                    >
                                        {{ optionLabel(p) }}
                                    </option>
                                </select>
                                <p
                                    v-if="
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
                                <InputError
                                    :message="
                                        form.errors[
                                            `items.${index}.product_id` as keyof typeof form.errors
                                        ]
                                    "
                                />
                            </td>
                            <td class="px-3 py-2">
                                <Input
                                    v-model="line.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    :max="
                                        availableFor(line.product_id) ??
                                        undefined
                                    "
                                    dir="ltr"
                                    class="text-start"
                                    :data-test="`line-qty-${index}`"
                                />
                                <InputError
                                    :message="
                                        form.errors[
                                            `items.${index}.quantity` as keyof typeof form.errors
                                        ]
                                    "
                                />
                            </td>
                            <td class="px-3 py-2 text-xs text-muted-foreground">
                                {{ unitOf(line.product_id) || '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    :data-test="`remove-line-${index}`"
                                    @click="removeLine(index)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="overLines.length"
                class="border-t border-border px-5 py-3 text-sm font-semibold text-status-critical"
                data-test="over-available"
            >
                فيه سطور كميتها أكبر من المتاح — صلّحها قبل الحفظ.
            </div>

            <InputError class="px-5 py-2" :message="form.errors.items" />
        </SectionCard>

        <SectionCard title="ملاحظات">
            <textarea
                v-model="form.notes"
                rows="2"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                placeholder="أي تعليمات للمخزن"
                data-test="order-notes"
            ></textarea>
            <InputError :message="form.errors.notes" />
        </SectionCard>

        <div class="flex justify-end gap-2">
            <Button
                type="submit"
                :disabled="form.processing || !canSubmit"
                data-test="save-order"
            >
                <Spinner v-if="form.processing" class="size-4" />
                حفظ وحجز الكميات
            </Button>
        </div>
    </form>
</template>
