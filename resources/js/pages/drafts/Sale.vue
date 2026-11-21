<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCheck, Plus, Save, Trash2 } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import ScanImagePanel from '@/components/ScanImagePanel.vue';
import ScanTypeMismatch from '@/components/ScanTypeMismatch.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { itemKey, useInvoiceScan } from '@/composables/useInvoiceScan';
import type { ScanReview } from '@/composables/useInvoiceScan';
import { formatMoney, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

/**
 * مراجعة فاتورة بيع ممسوحة.
 *
 * نفس فورم الفاتورة العادي بس متملّي من الصورة، وجنبه الصورة الأصلية
 * عشان المستخدم يقارن من غير ما يفتحها في تاب تاني.
 *
 * الفاتورة دي **مسودة**: مش موجودة في أي رصيد ولا تقرير لحد ما
 * يضغط «تأكيد الفاتورة».
 */

type ProductOption = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    salePrice: number;
    wholesalePrice: number | null;
    stock: Record<string, number>;
};

type DraftForm = {
    customer_id: number | null;
    date: string | null;
    discount: number | null;
    notes: string | null;
    items: {
        product_id: number | null;
        quantity: number | null;
        unit_price: number | null;
    }[];
};

const props = defineProps<{
    draft: {
        id: number;
        number: string;
        form: DraftForm;
        review: ScanReview | null;
        suggestedType: 'purchase' | 'sale' | null;
        warehouseId: number;
        scannedAt: string | null;
        image: { previewUrl: string; name: string; isPdf: boolean } | null;
    };
    customers: {
        id: number;
        name: string;
        city: string | null;
        priceTier: string;
    }[];
    warehouses: { id: number; name: string }[];
    products: ProductOption[];
    vatRate: number;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const scan = useInvoiceScan();

onMounted(() => scan.seed(props.draft.review, 'sale'));

type Line = {
    product_id: number | '';
    quantity: number | string;
    unit_price: number | string;
    discount: number | string;
};

const form = useForm({
    customer_id: (props.draft.form.customer_id ?? '') as number | '',
    warehouse_id: props.draft.warehouseId,
    date: props.draft.form.date ?? new Date().toISOString().slice(0, 10),
    due_date: '',
    discount: (props.draft.form.discount ?? 0) as number | string,
    notes: props.draft.form.notes ?? '',
    items: (props.draft.form.items ?? []).map((item) => ({
        product_id: (item.product_id ?? '') as number | '',
        quantity: item.quantity ?? 1,
        unit_price: item.unit_price ?? '',
        discount: 0,
    })) as Line[],
});

// المسح ممكن يرجع من غير أي سطر — الفورم محتاج سطر فاضي على الأقل
if (form.items.length === 0) {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_price: '',
        discount: 0,
    });
}

const productsById = computed(
    () => new Map(props.products.map((p) => [p.id, p])),
);

const selectedCustomer = computed(
    () =>
        props.customers.find((c) => c.id === Number(form.customer_id)) ?? null,
);

function priceFor(product: ProductOption): number {
    return selectedCustomer.value?.priceTier === 'wholesale' &&
        product.wholesalePrice
        ? product.wholesalePrice
        : product.salePrice;
}

/**
 * اختيار الصنف بيحط سعره — بس بس لو السطر لسه مفيهوش سعر من الورق.
 * السعر المكتوب على الفاتورة أهم من سعر الكتالوج.
 */
function onProductChange(line: Line, index: number) {
    const product = productsById.value.get(Number(line.product_id));

    if (product && line.unit_price === '') {
        line.unit_price = priceFor(product);
    }

    scan.confirm(itemKey(index, 'product_id'));
}

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

/** الرصيد المتاح للصنف في المخزن المختار */
function availableFor(productId: number | ''): number | null {
    const product = productsById.value.get(Number(productId));

    if (!product || !form.warehouse_id) {
        return null;
    }

    return product.stock[String(form.warehouse_id)] ?? 0;
}

const unresolved = computed(() => scan.pending.value.size);

function payload() {
    return (data: Record<string, unknown>) => ({
        ...data,
        due_date: null,
        items: (data.items as Line[]).filter((line) => line.product_id !== ''),
    });
}

/** «حفظ كمسودة» — بيحفظ المراجعة من غير ما يحرّك مخزون ولا رصيد */
function saveDraft() {
    form.transform(payload()).put(`/sales/drafts/${props.draft.id}`, {
        preserveScroll: true,
    });
}

/** «تأكيد الفاتورة» — من هنا بس بتتحرّك الأرقام */
function confirmInvoice() {
    form.transform(payload()).post(`/sales/drafts/${props.draft.id}/confirm`, {
        preserveScroll: true,
    });
}

function discard() {
    if (!window.confirm('متأكد إنك عايز تلغي المسودة دي؟')) {
        return;
    }

    router.delete(`/drafts/${props.draft.id}`);
}

/** نوع المسودة — ثابت، بيتبعت للتنبيه بتاع النوع الغلط */
const DRAFT_TYPE = 'sale' as const;

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="مراجعة فاتورة ممسوحة" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="مراجعة فاتورة ممسوحة"
            description="البيانات اتقرت من الصورة — راجعها وبعدين أكّد الفاتورة"
        >
            <template #actions>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    data-test="draft-discard"
                    @click="discard"
                >
                    <Trash2 class="size-4" />
                    إلغاء المسودة
                </Button>
            </template>
        </PageHeader>

        <ScanTypeMismatch
            v-if="draft.suggestedType"
            :draft-id="draft.id"
            :current="DRAFT_TYPE"
            :suggested="draft.suggestedType"
            :party-name="scan.review.value?.party.extracted ?? null"
        />

        <!-- شريط الحالة: المسودة ما بتلمسش حاجة -->
        <div
            class="rounded-lg border border-yellow-400/60 bg-yellow-50 px-4 py-3 text-sm dark:border-yellow-500/40 dark:bg-yellow-500/10"
            data-test="draft-notice"
        >
            <p class="font-medium">
                دي مسودة — المخزون وأرصدة التجار ما اتحركوش لسه.
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                <template v-if="unresolved > 0">
                    فيه
                    <span class="font-medium text-foreground">{{
                        unresolved
                    }}</span>
                    خانة ملوّنة بالأصفر محتاجة تراجعها. تحت كل واحدة مكتوب اللي
                    قريناه من الورق.
                </template>
                <template v-else>
                    كل الخانات اتطابقت. راجعها بسرعة واضغط «تأكيد الفاتورة».
                </template>
                أي تصحيح بتعمله بنفتكره، فالفاتورة الجاية من نفس العميل هتتقرا
                أظبط.
            </p>
        </div>

        <div class="grid gap-5 lg:grid-cols-5">
            <!-- الفورم -->
            <form
                class="flex flex-col gap-5 lg:col-span-3"
                @submit.prevent="confirmInvoice"
            >
                <SectionCard title="بيانات الفاتورة">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="customer_id">التاجر</Label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                :class="[
                                    SELECT_CLASS,
                                    scan.reviewClass('customer_id'),
                                ]"
                                required
                                data-test="draft-customer"
                                @change="scan.confirm('customer_id')"
                            >
                                <option value="" disabled>اختر التاجر</option>
                                <option
                                    v-for="c in customers"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.name
                                    }}{{ c.city ? ` — ${c.city}` : '' }}
                                </option>
                            </select>
                            <p
                                v-if="
                                    scan.needsReview('customer_id') &&
                                    scan.review.value?.party.extracted
                                "
                                class="text-xs text-muted-foreground"
                                data-test="draft-customer-hint"
                            >
                                مكتوب على الفاتورة: «{{
                                    scan.review.value.party.extracted
                                }}»
                            </p>
                            <InputError :message="form.errors.customer_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
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
                                <InputError
                                    :message="form.errors.warehouse_id"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="date">تاريخ الفاتورة</Label>
                                <Input
                                    id="date"
                                    v-model="form.date"
                                    type="date"
                                    required
                                    :class="scan.reviewClass('date')"
                                    data-test="draft-date"
                                    @input="scan.confirm('date')"
                                />
                                <InputError :message="form.errors.date" />
                            </div>
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
                            إضافة سطر
                        </Button>
                    </template>

                    <div class="scrollbar-thin overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b border-border text-xs text-muted-foreground"
                            >
                                <tr>
                                    <th
                                        class="px-3 py-2.5 text-start font-medium"
                                    >
                                        الصنف
                                    </th>
                                    <th
                                        class="w-20 px-3 py-2.5 text-start font-medium"
                                    >
                                        الكمية
                                    </th>
                                    <th
                                        class="w-28 px-3 py-2.5 text-start font-medium"
                                    >
                                        السعر
                                    </th>
                                    <th
                                        class="w-28 px-3 py-2.5 text-start font-medium"
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
                                            :class="[
                                                SELECT_CLASS,
                                                scan.reviewClass(
                                                    itemKey(
                                                        index,
                                                        'product_id',
                                                    ),
                                                ),
                                            ]"
                                            :data-test="`draft-line-product-${index}`"
                                            @change="
                                                onProductChange(line, index)
                                            "
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

                                        <!-- اللي مكتوب على الورق -->
                                        <p
                                            v-if="
                                                scan.needsReview(
                                                    itemKey(
                                                        index,
                                                        'product_id',
                                                    ),
                                                ) && scan.rawItem(index)
                                            "
                                            class="mt-1 text-xs text-muted-foreground"
                                            :data-test="`draft-line-hint-${index}`"
                                        >
                                            على الفاتورة: «{{
                                                scan.rawItem(index)!.extracted
                                                    .name ??
                                                scan.rawItem(index)!.extracted
                                                    .code
                                            }}»
                                        </p>
                                        <p
                                            v-else-if="
                                                availableFor(
                                                    line.product_id,
                                                ) !== null
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
                                                    availableFor(
                                                        line.product_id,
                                                    ),
                                                    productsById.get(
                                                        Number(line.product_id),
                                                    )?.unit,
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
                                            :data-test="`draft-line-qty-${index}`"
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <Input
                                            v-model="line.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            dir="ltr"
                                            :class="[
                                                'text-start',
                                                scan.reviewClass(
                                                    itemKey(
                                                        index,
                                                        'unit_price',
                                                    ),
                                                ),
                                            ]"
                                            :data-test="`draft-line-price-${index}`"
                                            @input="
                                                scan.confirm(
                                                    itemKey(
                                                        index,
                                                        'unit_price',
                                                    ),
                                                )
                                            "
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
                                            class="text-muted-foreground transition-colors hover:text-destructive"
                                            aria-label="حذف السطر"
                                            @click="removeLine(index)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <InputError
                        class="px-5 pb-3"
                        :message="form.errors.items"
                    />
                </SectionCard>

                <SectionCard title="الإجمالي">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">إجمالي البنود</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(subtotal) }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted-foreground">خصم</dt>
                            <dd class="w-32">
                                <Input
                                    v-model="form.discount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    dir="ltr"
                                    class="h-8 text-start"
                                />
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">
                                ضريبة القيمة المضافة
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(tax) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t border-border pt-2 text-base font-semibold"
                        >
                            <dt>الإجمالي</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(total) }}
                            </dd>
                        </div>
                    </dl>
                </SectionCard>

                <!-- الزرارين -->
                <div
                    class="sticky bottom-0 flex flex-wrap items-center justify-end gap-2 border-t border-border bg-background/90 py-3 backdrop-blur-sm"
                >
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        data-test="draft-save"
                        @click="saveDraft"
                    >
                        <Save class="size-4" />
                        حفظ كمسودة
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="draft-confirm"
                    >
                        <Spinner v-if="form.processing" class="size-4" />
                        <CheckCheck v-else class="size-4" />
                        تأكيد الفاتورة
                    </Button>
                </div>
            </form>

            <!-- الصورة الأصلية جنب الفورم -->
            <div class="lg:col-span-2">
                <ScanImagePanel :image="draft.image" />
            </div>
        </div>
    </div>
</template>
