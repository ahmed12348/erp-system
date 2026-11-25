<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Plus, ReceiptText, Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import ScanInvoiceDialog from '@/components/ScanInvoiceDialog.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney, formatNumber, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type CustomerOption = {
    id: number;
    name: string;
    city: string | null;
    priceTier: string;
    creditLimit: number | null;
    balance: number;
};

type ProductOption = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    category: string | null;
    salePrice: number;
    wholesalePrice: number | null;
    purchasePrice: number;
    minStock: number;
    stock: Record<string, number>;
};

/**
 * الفورم متملّي من طلب تحضير.
 *
 * نفس الشاشة بالظبط مش نسخة تانية منها — الفاتورة اللي جاية من طلب
 * لازم تمشي بنفس قواعد أي فاتورة بيع (الضريبة، فحص المتاح، تحذير حد
 * الائتمان)، ونسختين من الفورم كانوا هيفرقوا عن بعض مع أول تعديل.
 */
type Prefill = {
    preparationOrderId: number;
    orderNumber: string;
    customerId: number;
    warehouseId: number;
    items: { product_id: number; quantity: number; unit_price: number }[];
};

const props = defineProps<{
    customers: CustomerOption[];
    warehouses: { id: number; name: string }[];
    products: ProductOption[];
    vatRate: number;
    today: string;
    prefill?: Prefill;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/*
 * على نظام فاضي الفورم ده مالوش معنى — مفيش تجار ولا مخازن ولا أصناف
 * نختار منهم. بنوضّح الناقص بدل ما نعرض قوايم فاضية.
 */
const page = usePage();

/** زرار «مسح فاتورة» بيختفي لو المساعد مقفول */
const aiEnabled = computed(() => page.props.aiEnabled === true);

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
    unit_price: number | string;
    discount: number | string;
};

const form = useForm({
    customer_id: props.prefill?.customerId ?? ('' as number | ''),
    warehouse_id:
        props.prefill?.warehouseId ??
        props.warehouses[0]?.id ??
        ('' as number | ''),
    date: props.today,
    due_date: '',
    discount: 0 as number | string,
    notes: '',
    // بيتبعت مع الحفظ عشان الطلب يتعلّم «اتحوّل» والحجز يتفكّ
    preparation_order_id: props.prefill?.preparationOrderId ?? null,
    items: (props.prefill?.items.map((item) => ({
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount: 0,
    })) ?? [
        { product_id: '', quantity: 1, unit_price: '', discount: 0 },
    ]) as Line[],
});

const selectedCustomer = computed(
    () =>
        props.customers.find((c) => c.id === Number(form.customer_id)) ?? null,
);

const productsById = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);

/** السعر بيتملا تلقائي حسب شريحة التاجر (جملة الجملة أو تجزئة) */
function priceFor(product: ProductOption): number {
    const tier = selectedCustomer.value?.priceTier;

    return tier === 'wholesale' && product.wholesalePrice
        ? product.wholesalePrice
        : product.salePrice;
}

/** الرصيد المتاح للصنف في المخزن المختار */
function availableFor(productId: number | ''): number | null {
    const product = productsById.value.get(Number(productId));

    if (!product || !form.warehouse_id) {
        return null;
    }

    return product.stock[String(form.warehouse_id)] ?? 0;
}

function onProductChange(line: Line) {
    const product = productsById.value.get(Number(line.product_id));

    if (product) {
        line.unit_price = priceFor(product);
    }
}

// تغيير التاجر بيعيد تسعير كل السطور حسب شريحته
watch(selectedCustomer, () => {
    form.items.forEach((line) => {
        const product = productsById.value.get(Number(line.product_id));

        if (product) {
            line.unit_price = priceFor(product);
        }
    });
});

function addLine() {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_price: '',
        discount: 0,
    });
}

function removeLine(index: number) {
    form.items.splice(index, 1);

    if (form.items.length === 0) {
        addLine();
    }
}

function lineTotal(line: Line): number {
    const gross = Number(line.quantity || 0) * Number(line.unit_price || 0);

    return Math.max(gross - Number(line.discount || 0), 0);
}

const subtotal = computed(() =>
    form.items.reduce((sum, line) => sum + lineTotal(line), 0),
);
const taxable = computed(() =>
    Math.max(subtotal.value - Number(form.discount || 0), 0),
);
const tax = computed(() => taxable.value * props.vatRate);
const total = computed(() => taxable.value + tax.value);

/** تحذير حد المديونية — تحذير مش منع */
const creditWarning = computed(() => {
    const customer = selectedCustomer.value;

    if (!customer?.creditLimit) {
        return null;
    }

    const after = customer.balance + total.value;

    if (after <= customer.creditLimit) {
        return null;
    }

    return {
        after,
        limit: customer.creditLimit,
        excess: after - customer.creditLimit,
    };
});

/** السطور اللي الكمية فيها أكبر من المتاح — تحذير قبل ما السيرفر يرفض */
const shortLines = computed(() =>
    form.items
        .map((line, index) => ({ line, index }))
        .filter(({ line }) => {
            const available = availableFor(line.product_id);

            return available !== null && Number(line.quantity || 0) > available;
        }),
);

function submit() {
    form.transform((data) => ({
        ...data,
        due_date: data.due_date === '' ? null : data.due_date,
        items: data.items.filter((line) => line.product_id !== ''),
    })).post('/sales', { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="فاتورة بيع جديدة" />

    <div v-if="missing.length" class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="فاتورة بيع جديدة"
            description="اختر التاجر والمخزن وأضف الأصناف — الأسعار بتتملا حسب شريحة التاجر"
        />

        <SectionCard flush>
            <EmptyState
                large
                :icon="ReceiptText"
                title="لسه بدري على فاتورة البيع"
                :description="`لازم تسجّل ${missing.join(' و')} الأول، وتدخّل بضاعة بفاتورة شراء، عشان تقدر تبيع.`"
                :action="
                    missing.includes('مخزن')
                        ? { label: 'أضف أول مخزن', href: '/warehouses' }
                        : null
                "
                :secondary="{ label: 'رجوع للمبيعات', href: '/sales' }"
            />
        </SectionCard>
    </div>

    <form
        v-else
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            :title="
                prefill
                    ? `فاتورة بيع من طلب ${prefill.orderNumber}`
                    : 'فاتورة بيع جديدة'
            "
            :description="
                prefill
                    ? 'البنود والأسعار متملّية من الطلب — الحفظ بيعلّمه إنه اتحوّل ويفكّ الحجز'
                    : 'اختر التاجر والمخزن وأضف الأصناف — الأسعار بتتملا حسب شريحة التاجر'
            "
        >
            <!-- مسح فاتورة مالوش معنى لما الفورم جاي من طلب تحضير -->
            <template v-if="aiEnabled && !prefill" #actions>
                <ScanInvoiceDialog type="sale" />
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <!-- بيانات الفاتورة -->
            <SectionCard title="بيانات الفاتورة" class="lg:col-span-1">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="customer_id">التاجر</Label>
                        <select
                            id="customer_id"
                            v-model="form.customer_id"
                            :class="SELECT_CLASS"
                            required
                            data-test="invoice-customer"
                        >
                            <option value="" disabled>اختر التاجر</option>
                            <option
                                v-for="c in customers"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}{{ c.city ? ` — ${c.city}` : '' }}
                            </option>
                        </select>
                        <p
                            v-if="selectedCustomer"
                            class="text-xs text-muted-foreground"
                        >
                            الشريحة:
                            {{
                                selectedCustomer.priceTier === 'wholesale'
                                    ? 'جملة الجملة'
                                    : 'تجزئة'
                            }}
                            · الرصيد الحالي
                            {{ formatMoney(selectedCustomer.balance) }}
                        </p>
                        <InputError :message="form.errors.customer_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="warehouse_id">المخزن</Label>
                        <select
                            id="warehouse_id"
                            v-model="form.warehouse_id"
                            :class="SELECT_CLASS"
                            required
                            data-test="invoice-warehouse"
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

                    <div class="space-y-2">
                        <Label for="notes">ملاحظات</Label>
                        <Input id="notes" v-model="form.notes" />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>
            </SectionCard>

            <!-- الأصناف -->
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
                                    class="w-28 px-3 py-2.5 text-start font-medium"
                                >
                                    الكمية
                                </th>
                                <th
                                    class="w-32 px-3 py-2.5 text-start font-medium"
                                >
                                    السعر
                                </th>
                                <th
                                    class="w-28 px-3 py-2.5 text-start font-medium"
                                >
                                    خصم
                                </th>
                                <th
                                    class="w-32 px-3 py-2.5 text-start font-medium"
                                >
                                    الإجمالي
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
                                        :data-test="`line-product-${index}`"
                                        @change="onProductChange(line)"
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
                                            availableFor(line.product_id) !==
                                            null
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
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="line.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        dir="ltr"
                                        class="text-start"
                                        :data-test="`line-price-${index}`"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="line.discount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        dir="ltr"
                                        class="text-start"
                                    />
                                </td>
                                <td
                                    class="px-3 py-2 font-semibold tabular-nums"
                                >
                                    {{ formatMoney(lineTotal(line)) }}
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

        <!-- تحذيرات -->
        <div
            v-if="shortLines.length"
            class="flex items-start gap-3 rounded-xl border border-status-critical/30 bg-status-critical/5 p-4"
            data-test="stock-warning"
        >
            <AlertTriangle
                class="mt-0.5 size-5 shrink-0 text-status-critical"
            />
            <div class="text-sm">
                <p class="font-semibold text-status-critical">الرصيد مش كافي</p>
                <ul class="mt-1 space-y-0.5 text-muted-foreground">
                    <li v-for="{ line, index } in shortLines" :key="index">
                        {{ productsById.get(Number(line.product_id))?.name }} —
                        المتاح
                        {{ formatQuantity(availableFor(line.product_id)!) }}
                        والمطلوب
                        {{ formatQuantity(Number(line.quantity || 0)) }}
                    </li>
                </ul>
            </div>
        </div>

        <div
            v-if="creditWarning"
            class="flex items-start gap-3 rounded-xl border border-status-warning/30 bg-status-warning/5 p-4"
            data-test="credit-warning"
        >
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-status-warning" />
            <div class="text-sm">
                <p class="font-semibold text-status-warning">
                    الفاتورة دي هتعدّي حد مديونية التاجر
                </p>
                <p class="mt-0.5 text-muted-foreground">
                    الرصيد بعد الفاتورة
                    {{ formatMoney(creditWarning.after) }} والحد
                    {{ formatMoney(creditWarning.limit) }} — بزيادة
                    {{ formatMoney(creditWarning.excess) }}. تقدر تكمّل، بس خد
                    بالك.
                </p>
            </div>
        </div>

        <!-- الإجماليات -->
        <SectionCard title="الإجمالي">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="discount">خصم على الفاتورة</Label>
                    <Input
                        id="discount"
                        v-model="form.discount"
                        type="number"
                        step="0.01"
                        min="0"
                        dir="ltr"
                        class="text-start"
                        data-test="invoice-discount"
                    />
                    <InputError :message="form.errors.discount" />
                </div>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">إجمالي البنود</dt>
                        <dd
                            class="font-medium tabular-nums"
                            data-test="total-subtotal"
                        >
                            {{ formatMoney(subtotal) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">الخصم</dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatMoney(Number(form.discount || 0)) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">
                            ضريبة القيمة المضافة ({{
                                formatNumber(vatRate * 100)
                            }}%)
                        </dt>
                        <dd class="font-medium tabular-nums">
                            {{ formatMoney(tax) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-border pt-2 text-base font-bold"
                    >
                        <dt>الإجمالي النهائي</dt>
                        <dd class="tabular-nums" data-test="total-grand">
                            {{ formatMoney(total) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </SectionCard>

        <div class="flex justify-end gap-2">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="save-invoice"
            >
                <Spinner v-if="form.processing" />
                حفظ الفاتورة
            </Button>
        </div>
    </form>
</template>
