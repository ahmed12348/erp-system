<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, ShoppingCart, Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Supplier = {
    id: number;
    name: string;
    country: string | null;
    currency: string;
};

type ProductOption = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    lastCost: number;
};

const props = defineProps<{
    suppliers: Supplier[];
    warehouses: { id: number; name: string }[];
    products: ProductOption[];
    rates: Record<string, number>;
    today: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/*
 * على نظام فاضي الفورم ده مالوش معنى — مفيش مخازن ولا موردين ولا أصناف
 * نختار منهم. بنوضّح الناقص بدل ما نعرض قوايم فاضية.
 */
const missing = computed(() => {
    const items: string[] = [];

    if (!props.warehouses.length) {
        items.push('مخزن');
    }

    if (!props.suppliers.length) {
        items.push('مورد');
    }

    if (!props.products.length) {
        items.push('صنف');
    }

    return items;
});

type Line = {
    product_id: number | '';
    quantity: number | string;
    unit_price: number | string;
};

const form = useForm({
    supplier_id: '' as number | '',
    warehouse_id: props.warehouses[0]?.id ?? ('' as number | ''),
    date: props.today,
    due_date: '',
    currency: 'USD',
    exchange_rate: props.rates.USD ?? 1,
    shipping_cost: 0 as number | string,
    customs_cost: 0 as number | string,
    clearance_cost: 0 as number | string,
    discount: 0 as number | string,
    notes: '',
    items: [{ product_id: '', quantity: 1, unit_price: '' }] as Line[],
});

const selectedSupplier = computed(
    () =>
        props.suppliers.find((s) => s.id === Number(form.supplier_id)) ?? null,
);

// اختيار المورد بيجيب عملته وسعر الصرف المقترح
watch(selectedSupplier, (supplier) => {
    if (supplier) {
        form.currency = supplier.currency;
        form.exchange_rate = props.rates[supplier.currency] ?? 1;
    }
});

watch(
    () => form.currency,
    (currency) => {
        form.exchange_rate = props.rates[currency] ?? form.exchange_rate;
    },
);

const productsById = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);

function addLine() {
    form.items.push({ product_id: '', quantity: 1, unit_price: '' });
}

function removeLine(index: number) {
    form.items.splice(index, 1);

    if (form.items.length === 0) {
        addLine();
    }
}

function lineTotal(line: Line): number {
    return Number(line.quantity || 0) * Number(line.unit_price || 0);
}

const subtotal = computed(() =>
    form.items.reduce((sum, line) => sum + lineTotal(line), 0),
);
const total = computed(() =>
    Math.max(subtotal.value - Number(form.discount || 0), 0),
);
const rate = computed(() => Number(form.exchange_rate || 0));
const totalEgp = computed(() => total.value * rate.value);

const landedTotal = computed(
    () =>
        Number(form.shipping_cost || 0) +
        Number(form.customs_cost || 0) +
        Number(form.clearance_cost || 0),
);

/** التكلفة الواصلة = (سعر الوحدة × الصرف) + نصيب البند من المصاريف ÷ الكمية */
function landedUnitCost(line: Line): number {
    const quantity = Number(line.quantity || 0);

    if (quantity <= 0) {
        return 0;
    }

    const share =
        subtotal.value > 0
            ? landedTotal.value * (lineTotal(line) / subtotal.value)
            : 0;

    return Number(line.unit_price || 0) * rate.value + share / quantity;
}

function submit() {
    form.transform((data) => ({
        ...data,
        due_date: data.due_date === '' ? null : data.due_date,
        items: data.items.filter((line) => line.product_id !== ''),
    })).post('/purchases', { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="فاتورة شراء جديدة" />

    <div v-if="missing.length" class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="فاتورة شراء جديدة"
            description="فاتورة استيراد بعملة المورد — مصاريف الشحن والجمارك بتتوزّع على الأصناف بالتناسب"
        />

        <SectionCard flush>
            <EmptyState
                large
                :icon="ShoppingCart"
                title="لسه بدري على فاتورة الشراء"
                :description="`لازم تسجّل ${missing.join(' و')} الأول عشان تقدر تعمل فاتورة شراء.`"
                :action="
                    missing.includes('مخزن')
                        ? { label: 'أضف أول مخزن', href: '/warehouses' }
                        : null
                "
                :secondary="{ label: 'رجوع للمشتريات', href: '/purchases' }"
            />
        </SectionCard>
    </div>

    <form
        v-else
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="فاتورة شراء جديدة"
            description="فاتورة استيراد بعملة المورد — مصاريف الشحن والجمارك بتتوزّع على الأصناف بالتناسب"
        />

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="بيانات الفاتورة">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="supplier_id">المورد</Label>
                        <select
                            id="supplier_id"
                            v-model="form.supplier_id"
                            :class="SELECT_CLASS"
                            required
                            data-test="purchase-supplier"
                        >
                            <option value="" disabled>اختر المورد</option>
                            <option
                                v-for="s in suppliers"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name
                                }}{{ s.country ? ` — ${s.country}` : '' }}
                            </option>
                        </select>
                        <InputError :message="form.errors.supplier_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="warehouse_id">المخزن</Label>
                        <select
                            id="warehouse_id"
                            v-model="form.warehouse_id"
                            :class="SELECT_CLASS"
                            required
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

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="currency">العملة</Label>
                            <select
                                id="currency"
                                v-model="form.currency"
                                :class="SELECT_CLASS"
                                data-test="purchase-currency"
                            >
                                <option value="USD">دولار أمريكي</option>
                                <option value="EUR">يورو</option>
                                <option value="TRY">ليرة تركية</option>
                                <option value="EGP">جنيه مصري</option>
                            </select>
                            <InputError :message="form.errors.currency" />
                        </div>
                        <div class="space-y-2">
                            <Label for="exchange_rate">سعر الصرف</Label>
                            <Input
                                id="exchange_rate"
                                v-model="form.exchange_rate"
                                type="number"
                                step="0.0001"
                                min="0.0001"
                                dir="ltr"
                                class="text-start"
                                data-test="purchase-rate"
                            />
                            <InputError :message="form.errors.exchange_rate" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="date">تاريخ الفاتورة</Label>
                            <Input
                                id="date"
                                v-model="form.date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="due_date">الاستحقاق</Label>
                            <Input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                            />
                            <InputError :message="form.errors.due_date" />
                        </div>
                    </div>
                </div>
            </SectionCard>

            <SectionCard title="الأصناف" flush class="lg:col-span-2">
                <template #actions>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addLine"
                    >
                        <Plus class="size-4" />
                        إضافة سطر
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
                                <th
                                    class="w-24 px-3 py-2.5 text-start font-medium"
                                >
                                    الكمية
                                </th>
                                <th
                                    class="w-32 px-3 py-2.5 text-start font-medium"
                                >
                                    السعر ({{ form.currency }})
                                </th>
                                <th
                                    class="w-32 px-3 py-2.5 text-start font-medium"
                                >
                                    الإجمالي
                                </th>
                                <th
                                    class="w-36 px-3 py-2.5 text-start font-medium"
                                >
                                    التكلفة الواصلة
                                </th>
                                <th class="w-10 px-3 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(line, index) in form.items"
                                :key="index"
                            >
                                <td class="px-3 py-2">
                                    <select
                                        v-model="line.product_id"
                                        :class="SELECT_CLASS"
                                        :data-test="`pline-product-${index}`"
                                    >
                                        <option value="" disabled>
                                            اختر الصنف
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
                                            productsById.get(
                                                Number(line.product_id),
                                            )
                                        "
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        آخر تكلفة:
                                        {{
                                            formatMoney(
                                                productsById.get(
                                                    Number(line.product_id),
                                                )!.lastCost,
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
                                        :data-test="`pline-qty-${index}`"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="line.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        dir="ltr"
                                        class="text-start"
                                        :data-test="`pline-price-${index}`"
                                    />
                                </td>
                                <td
                                    class="px-3 py-2 font-semibold tabular-nums"
                                >
                                    {{
                                        formatMoney(
                                            lineTotal(line),
                                            form.currency,
                                            2,
                                        )
                                    }}
                                </td>
                                <td
                                    class="px-3 py-2 tabular-nums"
                                    :data-test="`pline-landed-${index}`"
                                >
                                    {{ formatMoney(landedUnitCost(line)) }}
                                </td>
                                <td class="px-3 py-2">
                                    <button
                                        type="button"
                                        class="text-muted-foreground transition-colors hover:text-status-critical"
                                        :aria-label="`حذف السطر ${index + 1}`"
                                        @click="removeLine(index)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <InputError class="px-5 pb-3" :message="form.errors.items" />
            </SectionCard>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <SectionCard
                title="مصاريف الاستيراد"
                description="بالجنيه المصري — بتتوزّع على الأصناف بالتناسب مع قيمة كل بند"
            >
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="shipping_cost">الشحن</Label>
                        <Input
                            id="shipping_cost"
                            v-model="form.shipping_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            dir="ltr"
                            class="text-start"
                            data-test="purchase-shipping"
                        />
                        <InputError :message="form.errors.shipping_cost" />
                    </div>
                    <div class="space-y-2">
                        <Label for="customs_cost">الجمارك</Label>
                        <Input
                            id="customs_cost"
                            v-model="form.customs_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            dir="ltr"
                            class="text-start"
                            data-test="purchase-customs"
                        />
                        <InputError :message="form.errors.customs_cost" />
                    </div>
                    <div class="space-y-2">
                        <Label for="clearance_cost">التخليص</Label>
                        <Input
                            id="clearance_cost"
                            v-model="form.clearance_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            dir="ltr"
                            class="text-start"
                            data-test="purchase-clearance"
                        />
                        <InputError :message="form.errors.clearance_cost" />
                    </div>
                </div>
                <p class="mt-3 text-sm text-muted-foreground">
                    إجمالي المصاريف
                    <span class="font-semibold text-foreground">
                        {{ formatMoney(landedTotal) }}
                    </span>
                </p>
            </SectionCard>

            <SectionCard title="الإجمالي">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="discount">خصم ({{ form.currency }})</Label>
                        <Input
                            id="discount"
                            v-model="form.discount"
                            type="number"
                            step="0.01"
                            min="0"
                            dir="ltr"
                            class="text-start"
                        />
                        <InputError :message="form.errors.discount" />
                        <Label for="notes" class="mt-3 block">ملاحظات</Label>
                        <Input id="notes" v-model="form.notes" />
                    </div>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">إجمالي البنود</dt>
                            <dd class="font-medium tabular-nums">
                                {{ formatMoney(subtotal, form.currency, 2) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">سعر الصرف</dt>
                            <dd class="font-medium tabular-nums">
                                {{ formatNumber(rate, 4) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t border-border pt-2 font-bold"
                        >
                            <dt>الإجمالي ({{ form.currency }})</dt>
                            <dd class="tabular-nums" data-test="purchase-total">
                                {{ formatMoney(total, form.currency, 2) }}
                            </dd>
                        </div>
                        <div class="flex justify-between text-base font-bold">
                            <dt>بالجنيه المصري</dt>
                            <dd
                                class="tabular-nums"
                                data-test="purchase-total-egp"
                            >
                                {{ formatMoney(totalEgp) }}
                            </dd>
                        </div>
                        <div class="flex justify-between text-muted-foreground">
                            <dt>+ مصاريف الاستيراد</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(landedTotal) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t border-border pt-2 font-semibold"
                        >
                            <dt>التكلفة الكلية الواصلة</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(totalEgp + landedTotal) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </SectionCard>
        </div>

        <div class="flex justify-end">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="save-purchase"
            >
                <Spinner v-if="form.processing" />
                حفظ الفاتورة
            </Button>
        </div>
    </form>
</template>
