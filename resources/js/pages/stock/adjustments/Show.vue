<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Printer } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatMoney, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    adjustment: {
        id: number;
        number: string;
        type: string;
        typeLabel: string;
        date: string;
        warehouse: string | null;
        reason: string;
        status: string;
        isApproved: boolean;
        value: number;
        notes: string | null;
        createdBy: string | null;
        approvedBy: string | null;
        approvedAt: string | null;
        stockCount: { id: number; number: string } | null;
        items: {
            id: number;
            sku: string | null;
            name: string | null;
            unit: string | null;
            quantity: number;
            unitCost: number;
            value: number;
        }[];
    };
    canApprove: boolean;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const approving = ref(false);

function approve() {
    if (
        !window.confirm(
            'الاعتماد هيحرّك البضاعة في المخزن فعلاً، ومابيترجعش. تمام؟',
        )
    ) {
        return;
    }

    approving.value = true;

    router.post(
        `/stock-adjustments/${props.adjustment.id}/approve`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                approving.value = false;
            },
        },
    );
}

function openPrint() {
    window.open(`/stock-adjustments/${props.adjustment.id}/print`, '_blank');
}
</script>

<template>
    <Head :title="adjustment.number" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="`${adjustment.typeLabel} ${adjustment.number}`"
            :description="`${adjustment.warehouse ?? '—'} · ${adjustment.reason}`"
        >
            <template #actions>
                <Button variant="outline" @click="openPrint">
                    <Printer class="size-4" />
                    طباعة
                </Button>
                <!-- الاعتماد هو اللي بيحرّك البضاعة -->
                <Button
                    v-if="canApprove && !adjustment.isApproved"
                    :disabled="approving"
                    data-test="approve-adjustment"
                    @click="approve"
                >
                    <Spinner v-if="approving" class="size-4" />
                    <CheckCircle2 v-else class="size-4" />
                    اعتماد وتحريك المخزون
                </Button>
            </template>
        </PageHeader>

        <!-- المسودة لازم تبان إنها مالهاش أثر لسه -->
        <div
            v-if="!adjustment.isApproved"
            class="rounded-xl border border-status-warning/40 bg-status-warning/10 px-5 py-3 text-sm text-status-warning"
            data-test="draft-notice"
        >
            الإذن ده لسه <strong>مسودة</strong> — المخزون مااتحركش، والأرصدة زي
            ما هي لحد ما يتعمد.
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="الإذن">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">التاريخ</dt>
                        <dd class="tabular-nums">
                            {{ formatDate(adjustment.date) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">السبب</dt>
                        <dd data-test="adjustment-reason">
                            {{ adjustment.reason }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">كتبه</dt>
                        <dd>{{ adjustment.createdBy ?? '—' }}</dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="الاعتماد">
                <dl v-if="adjustment.isApproved" class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">اعتمده</dt>
                        <dd data-test="approved-by">
                            {{ adjustment.approvedBy ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">وقت الاعتماد</dt>
                        <dd class="text-xs tabular-nums">
                            {{ adjustment.approvedAt ?? '—' }}
                        </dd>
                    </div>
                </dl>
                <p v-else class="text-sm text-muted-foreground">
                    لسه مااتعمدش.
                </p>

                <!-- الإذن اللي اتولّد من جرد بيشاور عليه -->
                <p
                    v-if="adjustment.stockCount"
                    class="mt-3 border-t border-border pt-3 text-xs"
                >
                    اتولّد من جرد
                    <Link
                        :href="`/stock-counts/${adjustment.stockCount.id}`"
                        class="font-medium tabular-nums hover:underline"
                        data-test="from-count"
                    >
                        {{ adjustment.stockCount.number }}
                    </Link>
                </p>
            </SectionCard>

            <SectionCard title="القيمة">
                <p
                    class="text-2xl font-bold tabular-nums"
                    data-test="adjustment-value"
                >
                    {{ formatMoney(adjustment.value) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    بالتكلفة المجمّدة على الإذن
                </p>
            </SectionCard>
        </div>

        <SectionCard title="الأصناف" flush>
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
                                التكلفة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                القيمة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in adjustment.items" :key="item.id">
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
                                {{ formatMoney(item.unitCost) }}
                            </td>
                            <td class="px-5 py-3 font-semibold tabular-nums">
                                {{ formatMoney(item.value) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>

        <SectionCard v-if="adjustment.notes" title="ملاحظات">
            <p class="text-sm whitespace-pre-line">{{ adjustment.notes }}</p>
        </SectionCard>
    </div>
</template>
