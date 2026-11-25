<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Undo2 } from '@lucide/vue';
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

type Line = {
    productId: number;
    sku: string | null;
    name: string | null;
    unit: string | null;
    unitPrice: number;
    sold: number;
    returned: number;
    /** السقف = المباع ناقص المرتجع قبل كده */
    returnable: number;
};

const props = defineProps<{
    invoice: {
        id: number;
        number: string;
        type: string;
        date: string;
        party: string | null;
        warehouse: string | null;
        currency: string;
        total: number;
    };
    lines: Line[];
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

const isPurchase = computed(() => props.invoice.type === 'purchase');

/*
 * كل بند بيبدأ بصفر — المستخدم بيختار اللي راجع.
 *
 * البداية بالكمية كاملة كانت هتخلّي «احفظ» من غير ما يبص يرجّع
 * الفاتورة كلها، وده أسهل غلط ممكن يحصل في الشاشة دي.
 */
const form = useForm({
    date: props.today,
    return_reason: '',
    notes: '',
    refund: { amount: '' as number | string, method: 'cash' },
    items: props.lines.map((line) => ({
        product_id: line.productId,
        quantity: 0 as number | string,
    })),
});

const linesByProduct = computed(
    () => new Map(props.lines.map((line) => [line.productId, line])),
);

function lineOf(productId: number): Line {
    return linesByProduct.value.get(productId)!;
}

function lineTotal(index: number): number {
    const item = form.items[index];

    return Number(item.quantity || 0) * lineOf(item.product_id).unitPrice;
}

const subtotal = computed(() =>
    form.items.reduce((sum, _item, index) => sum + lineTotal(index), 0),
);

/** الضريبة بنسبة الفاتورة الأصلية — السيرفر بيحسبها، وده عرض تقريبي */
const taxRate = computed(() =>
    props.invoice.total > 0 && subtotal.value > 0 ? 0.14 : 0,
);
const total = computed(() => subtotal.value * (1 + taxRate.value));

/** السطور اللي الكمية فيها عدّت المتاح للمرتجع */
const overLines = computed(() =>
    form.items.filter(
        (item) =>
            Number(item.quantity || 0) > lineOf(item.product_id).returnable,
    ),
);

const selectedCount = computed(
    () => form.items.filter((item) => Number(item.quantity || 0) > 0).length,
);

const canSubmit = computed(
    () =>
        selectedCount.value > 0 &&
        overLines.value.length === 0 &&
        form.return_reason !== '',
);

function fillAll() {
    form.items.forEach((item) => {
        item.quantity = lineOf(item.product_id).returnable;
    });
}

function submit() {
    form.transform((data) => ({
        ...data,
        items: data.items.filter((item) => Number(item.quantity || 0) > 0),
        refund:
            Number(data.refund.amount || 0) > 0
                ? {
                      amount: Number(data.refund.amount),
                      method: data.refund.method,
                  }
                : null,
    })).post(`/invoices/${props.invoice.id}/return`, { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head :title="`مرتجع على ${invoice.number}`" />

    <form
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            :title="`مرتجع على ${invoice.number}`"
            :description="`${invoice.party ?? '—'} · ${invoice.warehouse ?? '—'}`"
        />

        <SectionCard title="بيانات المرتجع">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="date">التاريخ</Label>
                    <Input
                        id="date"
                        v-model="form.date"
                        type="date"
                        dir="ltr"
                        class="text-start"
                        data-test="return-date"
                    />
                    <InputError :message="form.errors.date" />
                </div>

                <div class="space-y-2">
                    <Label for="return_reason">سبب المرتجع</Label>
                    <select
                        id="return_reason"
                        v-model="form.return_reason"
                        :class="SELECT_CLASS"
                        data-test="return-reason"
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

                <div class="space-y-2">
                    <Label>المخزن</Label>
                    <p class="pt-2 text-sm text-muted-foreground">
                        {{ invoice.warehouse ?? '—' }}
                        <span class="text-xs">(من الفاتورة الأصلية)</span>
                    </p>
                </div>
            </div>
        </SectionCard>

        <SectionCard title="البنود" flush>
            <template #actions>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    data-test="fill-all"
                    @click="fillAll"
                >
                    رجّع الكل
                </Button>
            </template>

            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الصنف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المباع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                مرتجع قبل كده
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المتاح للمرتجع
                            </th>
                            <th class="w-36 px-5 py-2.5 text-start font-medium">
                                الكمية الراجعة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                القيمة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="(item, index) in form.items"
                            :key="item.product_id"
                        >
                            <td class="px-5 py-3">
                                <span class="font-medium">
                                    {{ lineOf(item.product_id).name }}
                                </span>
                                <span
                                    class="ms-2 text-xs text-muted-foreground tabular-nums"
                                >
                                    {{ lineOf(item.product_id).sku }}
                                </span>
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{
                                    formatQuantity(
                                        lineOf(item.product_id).sold,
                                        lineOf(item.product_id).unit ??
                                            undefined,
                                    )
                                }}
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{
                                    lineOf(item.product_id).returned > 0
                                        ? formatQuantity(
                                              lineOf(item.product_id).returned,
                                          )
                                        : '—'
                                }}
                            </td>
                            <td class="px-5 py-3 font-medium tabular-nums">
                                {{
                                    formatQuantity(
                                        lineOf(item.product_id).returnable,
                                    )
                                }}
                            </td>
                            <td class="px-5 py-3">
                                <Input
                                    v-model="item.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    :max="lineOf(item.product_id).returnable"
                                    :disabled="
                                        lineOf(item.product_id).returnable <= 0
                                    "
                                    dir="ltr"
                                    class="h-8 text-start"
                                    :data-test="`return-qty-${item.product_id}`"
                                />
                                <InputError
                                    :message="
                                        form.errors[
                                            `items.${index}.quantity` as keyof typeof form.errors
                                        ]
                                    "
                                />
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{
                                    formatMoney(
                                        lineTotal(index),
                                        invoice.currency,
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="overLines.length"
                class="border-t border-border px-5 py-3 text-sm font-semibold text-status-critical"
                data-test="over-returnable"
            >
                فيه كميات أكبر من المتاح للمرتجع — صلّحها قبل الحفظ.
            </div>

            <InputError class="px-5 py-2" :message="form.errors.items" />
        </SectionCard>

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="رد نقدي (اختياري)" class="lg:col-span-2">
                <p class="mb-3 text-xs text-muted-foreground">
                    {{
                        isPurchase
                            ? 'المبلغ اللي المورد رده — بيدخل الخزينة.'
                            : 'المبلغ اللي اترد للتاجر — بيطلع من الخزينة. سيبه فاضي لو المرتجع هيفضل على الحساب.'
                    }}
                </p>
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
                            data-test="refund-amount"
                        />
                        <InputError :message="form.errors['refund.amount']" />
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
                        data-test="return-notes"
                    ></textarea>
                </div>
            </SectionCard>

            <SectionCard title="الإجمالي">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">بنود مختارة</dt>
                        <dd class="tabular-nums">{{ selectedCount }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">قيمة البنود</dt>
                        <dd class="tabular-nums">
                            {{ formatMoney(subtotal, invoice.currency) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between gap-3 border-t border-border pt-2 font-semibold"
                    >
                        <dt>إجمالي المرتجع</dt>
                        <dd class="tabular-nums" data-test="return-total">
                            {{ formatMoney(total, invoice.currency) }}
                        </dd>
                    </div>
                </dl>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :disabled="form.processing || !canSubmit"
                    data-test="save-return"
                >
                    <Spinner v-if="form.processing" class="size-4" />
                    <Undo2 v-else class="size-4" />
                    تسجيل المرتجع
                </Button>
            </SectionCard>
        </div>
    </form>
</template>
