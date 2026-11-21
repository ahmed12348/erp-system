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
import { formatMoney } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

/**
 * مراجعة فاتورة شراء ممسوحة — نفس فكرة فاتورة البيع، بس معاها عملة
 * وسعر صرف ومصاريف استيراد.
 */

type DraftForm = {
    supplier_id: number | null;
    date: string | null;
    currency: string | null;
    exchange_rate: number | null;
    discount: number | null;
    shipping_cost: number | null;
    customs_cost: number | null;
    clearance_cost: number | null;
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
        image: { previewUrl: string; name: string; isPdf: boolean } | null;
    };
    suppliers: {
        id: number;
        name: string;
        country: string | null;
        currency: string;
    }[];
    warehouses: { id: number; name: string }[];
    products: { id: number; sku: string; name: string; unit: string }[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const scan = useInvoiceScan();

onMounted(() => scan.seed(props.draft.review, 'purchase'));

type Line = {
    product_id: number | '';
    quantity: number | string;
    unit_price: number | string;
};

const form = useForm({
    supplier_id: (props.draft.form.supplier_id ?? '') as number | '',
    warehouse_id: props.draft.warehouseId,
    date: props.draft.form.date ?? new Date().toISOString().slice(0, 10),
    due_date: '',
    // الجنيه مش الدولار — المسح مابيفترضش عملة أجنبية من غير ما تكون مكتوبة
    currency: props.draft.form.currency ?? 'EGP',
    exchange_rate: (props.draft.form.exchange_rate ?? 1) as number | string,
    shipping_cost: (props.draft.form.shipping_cost ?? 0) as number | string,
    customs_cost: (props.draft.form.customs_cost ?? 0) as number | string,
    clearance_cost: (props.draft.form.clearance_cost ?? 0) as number | string,
    discount: (props.draft.form.discount ?? 0) as number | string,
    notes: props.draft.form.notes ?? '',
    items: (props.draft.form.items ?? []).map((item) => ({
        product_id: (item.product_id ?? '') as number | '',
        quantity: item.quantity ?? 1,
        unit_price: item.unit_price ?? '',
    })) as Line[],
});

if (form.items.length === 0) {
    form.items.push({ product_id: '', quantity: 1, unit_price: '' });
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

function lineTotal(line: Line): number {
    return Number(line.quantity || 0) * Number(line.unit_price || 0);
}

const subtotal = computed(() =>
    form.items.reduce((sum, line) => sum + lineTotal(line), 0),
);
const total = computed(() =>
    Math.max(subtotal.value - Number(form.discount || 0), 0),
);
const landedTotal = computed(
    () =>
        Number(form.shipping_cost || 0) +
        Number(form.customs_cost || 0) +
        Number(form.clearance_cost || 0),
);

const unresolved = computed(() => scan.pending.value.size);

function payload() {
    return (data: Record<string, unknown>) => ({
        ...data,
        due_date: null,
        items: (data.items as Line[]).filter((line) => line.product_id !== ''),
    });
}

function saveDraft() {
    form.transform(payload()).put(`/purchases/drafts/${props.draft.id}`, {
        preserveScroll: true,
    });
}

function confirmInvoice() {
    form.transform(payload()).post(
        `/purchases/drafts/${props.draft.id}/confirm`,
        { preserveScroll: true },
    );
}

function discard() {
    if (!window.confirm('متأكد إنك عايز تلغي المسودة دي؟')) {
        return;
    }

    router.delete(`/drafts/${props.draft.id}`);
}

/** نوع المسودة — ثابت، بيتبعت للتنبيه بتاع النوع الغلط */
const DRAFT_TYPE = 'purchase' as const;

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="مراجعة فاتورة شراء ممسوحة" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="مراجعة فاتورة شراء ممسوحة"
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

        <div
            class="rounded-lg border border-yellow-400/60 bg-yellow-50 px-4 py-3 text-sm dark:border-yellow-500/40 dark:bg-yellow-500/10"
            data-test="draft-notice"
        >
            <p class="font-medium">
                دي مسودة — المخزون وأرصدة الموردين ما اتحركوش لسه.
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                <template v-if="unresolved > 0">
                    فيه
                    <span class="font-medium text-foreground">{{
                        unresolved
                    }}</span>
                    خانة ملوّنة بالأصفر محتاجة تراجعها.
                </template>
                <template v-else>
                    كل الخانات اتطابقت. راجعها بسرعة واضغط «تأكيد الفاتورة».
                </template>
            </p>
        </div>

        <div class="grid gap-5 lg:grid-cols-5">
            <form
                class="flex flex-col gap-5 lg:col-span-3"
                @submit.prevent="confirmInvoice"
            >
                <SectionCard title="بيانات الفاتورة">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="supplier_id">المورد</Label>
                            <select
                                id="supplier_id"
                                v-model="form.supplier_id"
                                :class="[
                                    SELECT_CLASS,
                                    scan.reviewClass('supplier_id'),
                                ]"
                                required
                                data-test="draft-supplier"
                                @change="scan.confirm('supplier_id')"
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
                            <p
                                v-if="
                                    scan.needsReview('supplier_id') &&
                                    scan.review.value?.party.extracted
                                "
                                class="text-xs text-muted-foreground"
                            >
                                مكتوب على الفاتورة: «{{
                                    scan.review.value.party.extracted
                                }}»
                            </p>
                            <InputError :message="form.errors.supplier_id" />
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
                            </div>
                            <div class="space-y-2">
                                <Label for="date">تاريخ الفاتورة</Label>
                                <Input
                                    id="date"
                                    v-model="form.date"
                                    type="date"
                                    required
                                    :class="scan.reviewClass('date')"
                                    @input="scan.confirm('date')"
                                />
                                <InputError :message="form.errors.date" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="currency">العملة</Label>
                                <select
                                    id="currency"
                                    v-model="form.currency"
                                    :class="[
                                        SELECT_CLASS,
                                        scan.reviewClass('currency'),
                                    ]"
                                    @change="scan.confirm('currency')"
                                >
                                    <option value="USD">دولار أمريكي</option>
                                    <option value="EUR">يورو</option>
                                    <option value="TRY">ليرة تركية</option>
                                    <option value="EGP">جنيه مصري</option>
                                </select>
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
                                    :class="[
                                        'text-start',
                                        scan.reviewClass('exchange_rate'),
                                    ]"
                                    @input="scan.confirm('exchange_rate')"
                                />
                                <InputError
                                    :message="form.errors.exchange_rate"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
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
                                />
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
                                />
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
                                />
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
                                        السعر ({{ form.currency }})
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
                                                scan.confirm(
                                                    itemKey(
                                                        index,
                                                        'product_id',
                                                    ),
                                                )
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
                                    </td>
                                    <td class="px-3 py-2">
                                        <Input
                                            v-model="line.quantity"
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            dir="ltr"
                                            class="text-start"
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
                                        {{
                                            formatMoney(
                                                lineTotal(line),
                                                form.currency,
                                            )
                                        }}
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
                                {{ formatMoney(subtotal, form.currency) }}
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
                                مصاريف الاستيراد
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(landedTotal) }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t border-border pt-2 text-base font-semibold"
                        >
                            <dt>الإجمالي</dt>
                            <dd class="tabular-nums">
                                {{ formatMoney(total, form.currency) }}
                            </dd>
                        </div>
                    </dl>
                </SectionCard>

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

            <div class="lg:col-span-2">
                <ScanImagePanel :image="draft.image" />
            </div>
        </div>
    </div>
</template>
