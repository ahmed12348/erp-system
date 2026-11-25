<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, FileText, Printer, ReceiptText } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import ShareOnWhatsApp from '@/components/ShareOnWhatsApp.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

/* ولا حقل سعر — لا في النوع ولا في الجدول تحت. */
type Item = {
    id: number;
    sku: string | null;
    name: string | null;
    unit: string | null;
    quantity: number;
    preparedQuantity: number | null;
};

type Order = {
    id: number;
    number: string;
    date: string;
    status: string;
    statusLabel: string;
    isEditable: boolean;
    notes: string | null;
    preparedAt: string | null;
    cancelledAt: string | null;
    createdBy: string | null;
    warehouse: string | null;
    customer: {
        id: number;
        name: string;
        phone: string | null;
        city: string | null;
        address: string | null;
    } | null;
    invoice: { id: number; number: string } | null;
    items: Item[];
};

const props = defineProps<{
    order: Order;
    /** رابط موقّع للـ PDF — بيتبعت على الواتساب وبيفتح من غير تسجيل دخول */
    pdfUrl: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const STATUS_TONE: Record<string, string> = {
    open: 'bg-status-warning/10 text-status-warning',
    prepared: 'bg-primary/10 text-primary',
    converted: 'bg-status-good/10 text-status-good',
    cancelled: 'bg-muted text-muted-foreground',
};

/*
 * الكميات اللي المخزن بيعدّلها وهو بيحضّر.
 *
 * بتبدأ من المحضّر لو اتسجّل قبل كده، وإلا من المطلوب — فلو الصفحة
 * اتفتحت تاني بعد تحضير، اللي بيبان هو آخر حاجة اتحفظت.
 */
const prepared = reactive<Record<number, number | string>>(
    Object.fromEntries(
        props.order.items.map((item) => [
            item.id,
            item.preparedQuantity ?? item.quantity,
        ]),
    ),
);

const saving = ref(false);

function markPrepared() {
    saving.value = true;

    router.post(
        `/preparation-orders/${props.order.id}/prepare`,
        {
            items: props.order.items.map((item) => ({
                id: item.id,
                prepared_quantity: Number(prepared[item.id] ?? item.quantity),
            })),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function cancel() {
    if (!window.confirm('هتلغي الطلب والكميات المحجوزة هتترجع. تمام؟')) {
        return;
    }

    router.post(
        `/preparation-orders/${props.order.id}/cancel`,
        {},
        { preserveScroll: true },
    );
}

function openPrint() {
    window.open(`/preparation-orders/${props.order.id}/print`, '_blank');
}

/** ملخّص عربي قصير للواتساب — الرقم والتاجر والأصناف وكمياتها */
const whatsappText = computed(() => {
    const lines = props.order.items.map((item) => {
        const quantity = formatQuantity(item.preparedQuantity ?? item.quantity);

        return `• ${item.name ?? '—'} — ${quantity} ${item.unit ?? ''}`.trim();
    });

    return [
        `طلب تحضير ${props.order.number}`,
        `التاجر: ${props.order.customer?.name ?? '—'}`,
        `المخزن: ${props.order.warehouse ?? '—'}`,
        `التاريخ: ${props.order.date}`,
        '',
        ...lines,
    ].join('\n');
});

const fileName = computed(() => `طلب تحضير ${props.order.number}.pdf`);
</script>

<template>
    <Head :title="order.number" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="`طلب تحضير ${order.number}`"
            :description="`${order.customer?.name ?? '—'} · ${order.warehouse ?? '—'}`"
        >
            <template #actions>
                <Button variant="outline" @click="openPrint">
                    <Printer class="size-4" />
                    طباعة
                </Button>
                <Button variant="outline" as-child>
                    <a :href="pdfUrl" data-test="download-pdf">
                        <FileText class="size-4" />
                        تحميل PDF
                    </a>
                </Button>
                <ShareOnWhatsApp
                    :text="whatsappText"
                    :file-url="pdfUrl"
                    :file-name="fileName"
                />
                <Button
                    v-if="order.isEditable"
                    as-child
                    data-test="convert-to-invoice"
                >
                    <Link :href="`/preparation-orders/${order.id}/convert`">
                        <ReceiptText class="size-4" />
                        تحويل لفاتورة بيع
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <!-- بيانات الطلب -->
        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="الطلب">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">الحالة</dt>
                        <dd>
                            <span
                                class="rounded-md px-2 py-0.5 text-xs font-medium"
                                :class="STATUS_TONE[order.status]"
                                data-test="order-status"
                            >
                                {{ order.statusLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">التاريخ</dt>
                        <dd class="tabular-nums">
                            {{ formatDate(order.date) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">فتحه</dt>
                        <dd>{{ order.createdBy ?? '—' }}</dd>
                    </div>
                    <div
                        v-if="order.invoice"
                        class="flex justify-between gap-3"
                    >
                        <dt class="text-muted-foreground">الفاتورة</dt>
                        <dd>
                            <Link
                                :href="`/sales/${order.invoice.id}`"
                                class="tabular-nums hover:underline"
                                data-test="linked-invoice"
                            >
                                {{ order.invoice.number }}
                            </Link>
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="التاجر">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">الاسم</dt>
                        <dd>{{ order.customer?.name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">تليفون</dt>
                        <dd dir="ltr" class="tabular-nums">
                            {{ order.customer?.phone ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">العنوان</dt>
                        <dd class="text-end">
                            {{ order.customer?.city ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="ملاحظات">
                <p class="text-sm whitespace-pre-line">
                    {{ order.notes || 'مفيش ملاحظات' }}
                </p>
            </SectionCard>
        </div>

        <!-- الأصناف — كميات بس -->
        <SectionCard
            title="الأصناف"
            description="ورقة المخزن — مفيش أسعار هنا"
            flush
        >
            <template v-if="order.isEditable" #actions>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="saving"
                    data-test="mark-prepared"
                    @click="markPrepared"
                >
                    تم التحضير
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    data-test="cancel-order"
                    @click="cancel"
                >
                    <Ban class="size-4" />
                    إلغاء الطلب
                </Button>
            </template>

            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الكود
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الصنف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الوحدة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المطلوب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المحضّر
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in order.items" :key="item.id">
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ item.sku ?? '—' }}
                            </td>
                            <td class="px-5 py-3">{{ item.name ?? '—' }}</td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ item.unit ?? '—' }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatQuantity(item.quantity) }}
                            </td>
                            <td class="px-5 py-3">
                                <!-- المخزن بيعدّل هنا وهو بيجمّع -->
                                <Input
                                    v-if="order.isEditable"
                                    v-model="prepared[item.id]"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    dir="ltr"
                                    class="h-8 w-28 text-start"
                                    :data-test="`prepared-${item.id}`"
                                />
                                <span v-else class="tabular-nums">
                                    {{
                                        formatQuantity(
                                            item.preparedQuantity ??
                                                item.quantity,
                                        )
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>
    </div>
</template>
