<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type ProductOption = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    purchasePrice: number;
    salePrice: number;
};

const props = defineProps<{
    type: string;
    parties: { id: number; name: string }[];
    warehouses: { id: number; name: string }[];
    products: ProductOption[];
    reasons: { value: string; label: string }[];
    methods: { value: string; label: string }[];
    today: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const isPurchase = computed(() => props.type === 'purchase_return');

type Line = {
    product_id: number | '';
    quantity: number | string;
    unit_price: number | string;
};

const form = useForm({
    type: props.type,
    customer_id: '' as number | '',
    supplier_id: '' as number | '',
    warehouse_id: props.warehouses[0]?.id ?? ('' as number | ''),
    date: props.today,
    return_reason: '',
    notes: '',
    refund: { amount: '' as number | string, method: 'cash' },
    items: [{ product_id: '', quantity: 1, unit_price: '' }] as Line[],
});

const productsById = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);

/** السعر بيتقترح من الكتالوج — مفيش فاتورة نرجع لها */
function onProductChange(line: Line) {
    const product = productsById.value.get(Number(line.product_id));

    if (product && line.unit_price === '') {
        line.unit_price = isPurchase.value
            ? product.purchasePrice
            : product.salePrice;
    }
}

function addLine() {
    form.items.push({ product_id: '', quantity: 1, unit_price: '' });
}

function removeLine(index: number) {
    form.items.splice(index, 1);

    if (form.items.length === 0) {
        addLine();
    }
}

const subtotal = computed(() =>
    form.items.reduce(
        (sum, line) =>
            sum + Number(line.quantity || 0) * Number(line.unit_price || 0),
        0,
    ),
);

function submit() {
    form.transform((data) => ({
        ...data,
        customer_id: isPurchase.value ? null : data.customer_id,
        supplier_id: isPurchase.value ? data.supplier_id : null,
        items: data.items.filter((line) => line.product_id !== ''),
        refund:
            Number(data.refund.amount || 0) > 0
                ? {
                      amount: Number(data.refund.amount),
                      method: data.refund.method,
                  }
                : null,
    })).post('/returns/orphan', { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="مرتجع من غير فاتورة" />

    <form
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="مرتجع من غير فاتورة أصلية"
            :description="isPurchase ? 'مرتجع مشتريات' : 'مرتجع مبيعات'"
        />

        <!-- التحذير أول حاجة على الشاشة، مش تحت -->
        <div
            class="flex items-start gap-3 rounded-xl border border-status-warning/40 bg-status-warning/10 px-5 py-4 text-sm"
            data-test="orphan-warning"
        >
            <TriangleAlert class="mt-0.5 size-5 shrink-0 text-status-warning" />
            <div class="space-y-1">
                <p class="font-semibold text-status-warning">
                    المستند ده هيتعلّم «من غير فاتورة أصلية»
                </p>
                <p class="text-muted-foreground">
                    مابينفعش يتطابق مع أي فاتورة، والمراجعة هتفرزه لوحده. لو
                    الفاتورة الأصلية موجودة في النظام، اعمل المرتجع من عليها بدل
                    كده.
                </p>
            </div>
        </div>

        <SectionCard title="بيانات المرتجع">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="space-y-2">
                    <Label for="party">
                        {{ isPurchase ? 'المورد' : 'التاجر' }}
                    </Label>
                    <select
                        v-if="isPurchase"
                        id="party"
                        v-model="form.supplier_id"
                        :class="SELECT_CLASS"
                        data-test="orphan-party"
                    >
                        <option value="" disabled>اختار المورد</option>
                        <option v-for="p in parties" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                    <select
                        v-else
                        id="party"
                        v-model="form.customer_id"
                        :class="SELECT_CLASS"
                        data-test="orphan-party"
                    >
                        <option value="" disabled>اختار التاجر</option>
                        <option v-for="p in parties" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                    <InputError
                        :message="
                            form.errors.customer_id ?? form.errors.supplier_id
                        "
                    />
                </div>

                <div class="space-y-2">
                    <Label for="warehouse_id">المخزن</Label>
                    <select
                        id="warehouse_id"
                        v-model="form.warehouse_id"
                        :class="SELECT_CLASS"
                        data-test="orphan-warehouse"
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
                    />
                    <InputError :message="form.errors.date" />
                </div>

                <div class="space-y-2">
                    <Label for="return_reason">السبب</Label>
                    <select
                        id="return_reason"
                        v-model="form.return_reason"
                        :class="SELECT_CLASS"
                        data-test="orphan-reason"
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
                    <InputError :message="form.errors.return_reason" />
                </div>
            </div>
        </SectionCard>

        <SectionCard title="البنود" flush>
            <template #actions>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
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
                            <th class="w-36 px-3 py-2.5 text-start font-medium">
                                السعر
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
                                    :data-test="`orphan-product-${index}`"
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
                            </td>
                            <td class="px-3 py-2">
                                <Input
                                    v-model="line.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    dir="ltr"
                                    class="text-start"
                                    :data-test="`orphan-qty-${index}`"
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
                                    :data-test="`orphan-price-${index}`"
                                />
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
                </table>
            </div>

            <InputError class="px-5 py-2" :message="form.errors.items" />
        </SectionCard>

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="رد نقدي (اختياري)" class="lg:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="refund_amount">المبلغ</Label>
                        <Input
                            id="refund_amount"
                            v-model="form.refund.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            dir="ltr"
                            class="text-start"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="refund_method">طريقة الدفع</Label>
                        <select
                            id="refund_method"
                            v-model="form.refund.method"
                            :class="SELECT_CLASS"
                        >
                            <option
                                v-for="m in methods"
                                :key="m.value"
                                :value="m.value"
                            >
                                {{ m.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    <Label for="notes">ملاحظات</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    ></textarea>
                </div>
            </SectionCard>

            <SectionCard title="الإجمالي">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3 font-semibold">
                        <dt>قيمة البنود</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(subtotal) }}
                        </dd>
                    </div>
                </dl>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :disabled="form.processing"
                    data-test="save-orphan-return"
                >
                    <Spinner v-if="form.processing" class="size-4" />
                    تسجيل المرتجع
                </Button>
            </SectionCard>
        </div>
    </form>
</template>
