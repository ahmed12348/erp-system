<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Printer, Send } from '@lucide/vue';
import { computed } from 'vue';
import AttachmentPanel from '@/components/AttachmentPanel.vue';
import DownloadMenu from '@/components/DownloadMenu.vue';
import EtaBadge from '@/components/EtaBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type ReturnDoc = {
    id: number;
    number: string;
    type: string;
    date: string;
    currency: string;
    subtotal: number;
    tax: number;
    total: number;
    reason: string | null;
    notes: string | null;
    isOrphan: boolean;
    etaStatus: string | null;
    etaUuid: string | null;
    createdBy: string | null;
    warehouse: string | null;
    party: string | null;
    partyLink: string | null;
    original: {
        id: number;
        number: string;
        date: string;
        link: string;
    } | null;
    items: {
        id: number;
        sku: string | null;
        name: string | null;
        unit: string | null;
        quantity: number;
        unitPrice: number;
        total: number;
    }[];
    refunds: { id: number; date: string; amount: number; method: string }[];
    attachments: unknown[];
};

const props = defineProps<{
    doc: ReturnDoc;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const isSaleReturn = computed(() => props.doc.type === 'sale_return');

function openPrint() {
    window.open(`/returns/${props.doc.id}/print`, '_blank');
}

/** المرتجع بيتبعت للمنظومة كإشعار خصم */
function sendToEta() {
    router.post(`/returns/${props.doc.id}/eta`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="doc.number" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="`مرتجع ${doc.number}`"
            :description="`${doc.party ?? '—'} · ${doc.warehouse ?? '—'}`"
        >
            <template #actions>
                <Button variant="outline" @click="openPrint">
                    <Printer class="size-4" />
                    طباعة
                </Button>
                <DownloadMenu :href="`/exports/invoices/${doc.id}`" />
                <Button
                    v-if="isSaleReturn && !doc.etaUuid"
                    variant="outline"
                    data-test="send-eta"
                    @click="sendToEta"
                >
                    <Send class="size-4" />
                    إرسال إشعار خصم
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="المرتجع">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">التاريخ</dt>
                        <dd class="tabular-nums">
                            {{ formatDate(doc.date) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">السبب</dt>
                        <dd data-test="return-reason">
                            {{ doc.reason ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">سجّله</dt>
                        <dd>{{ doc.createdBy ?? '—' }}</dd>
                    </div>
                    <div v-if="isSaleReturn" class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">المنظومة</dt>
                        <dd>
                            <EtaBadge :status="doc.etaStatus" />
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="الفاتورة الأصلية">
                <div v-if="doc.original" class="space-y-2 text-sm">
                    <Link
                        :href="doc.original.link"
                        class="font-medium tabular-nums hover:underline"
                        data-test="original-invoice"
                    >
                        {{ doc.original.number }}
                    </Link>
                    <p class="text-xs text-muted-foreground">
                        بتاريخ {{ formatDate(doc.original.date) }}
                    </p>
                </div>

                <!--
                    المرتجع من غير أصل لازم يبان إنه كده على الشاشة —
                    المراجعة بتفرزه، وما ينفعش يتطابق مع أي حاجة.
                -->
                <div v-else class="space-y-2" data-test="orphan-notice">
                    <p
                        class="inline-block rounded-md bg-status-warning/10 px-2 py-1 text-xs font-semibold text-status-warning"
                    >
                        مرتجع من غير فاتورة أصلية
                    </p>
                    <p class="text-xs text-muted-foreground">
                        اتسجّل بإذن المدير — مفيش فاتورة يتطابق معاها.
                    </p>
                </div>
            </SectionCard>

            <SectionCard :title="isSaleReturn ? 'التاجر' : 'المورد'">
                <Link
                    v-if="doc.partyLink"
                    :href="doc.partyLink"
                    class="text-sm font-medium hover:underline"
                >
                    {{ doc.party }}
                </Link>
                <p v-else class="text-sm">{{ doc.party ?? '—' }}</p>

                <div
                    v-if="doc.refunds.length"
                    class="mt-3 space-y-1 border-t border-border pt-3 text-xs"
                >
                    <p class="font-medium">
                        {{ isSaleReturn ? 'اترد نقدي' : 'المورد رد' }}
                    </p>
                    <p
                        v-for="refund in doc.refunds"
                        :key="refund.id"
                        class="flex justify-between gap-3 text-muted-foreground"
                    >
                        <span>{{ refund.method }}</span>
                        <span class="tabular-nums">
                            {{ formatMoney(refund.amount, doc.currency) }}
                        </span>
                    </p>
                </div>
            </SectionCard>
        </div>

        <SectionCard title="البنود" flush>
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
                                الكمية
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                السعر
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإجمالي
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in doc.items" :key="item.id">
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ item.sku ?? '—' }}
                            </td>
                            <td class="px-5 py-3">{{ item.name ?? '—' }}</td>
                            <td class="px-5 py-3 tabular-nums">
                                {{
                                    formatQuantity(
                                        item.quantity,
                                        item.unit ?? undefined,
                                    )
                                }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(item.unitPrice, doc.currency) }}
                            </td>
                            <td class="px-5 py-3 font-semibold tabular-nums">
                                {{ formatMoney(item.total, doc.currency) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40">
                        <tr>
                            <td class="px-5 py-2.5" colspan="4">قيمة البنود</td>
                            <td class="px-5 py-2.5 tabular-nums">
                                {{ formatMoney(doc.subtotal, doc.currency) }}
                            </td>
                        </tr>
                        <tr v-if="doc.tax > 0">
                            <td class="px-5 py-2.5" colspan="4">الضريبة</td>
                            <td class="px-5 py-2.5 tabular-nums">
                                {{ formatMoney(doc.tax, doc.currency) }}
                            </td>
                        </tr>
                        <tr class="font-bold">
                            <td class="px-5 py-3" colspan="4">
                                إجمالي المرتجع
                            </td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                data-test="return-total"
                            >
                                {{ formatMoney(doc.total, doc.currency) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </SectionCard>

        <SectionCard v-if="doc.notes" title="ملاحظات">
            <p class="text-sm whitespace-pre-line">{{ doc.notes }}</p>
        </SectionCard>

        <AttachmentPanel
            :attachments="doc.attachments as never[]"
            :upload-url="`/invoices/${doc.id}/attachments`"
        />
    </div>
</template>
