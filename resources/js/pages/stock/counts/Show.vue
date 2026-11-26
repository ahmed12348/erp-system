<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, CheckCircle2, Printer, Save } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatNumber, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Item = {
    id: number;
    sku: string | null;
    name: string | null;
    unit: string | null;
    systemQuantity: number;
    countedQuantity: number | null;
    difference: number | null;
};

const props = defineProps<{
    count: {
        id: number;
        number: string;
        date: string;
        warehouse: string | null;
        category: string | null;
        status: string;
        statusLabel: string;
        isEditable: boolean;
        notes: string | null;
        createdBy: string | null;
        approvedBy: string | null;
        approvedAt: string | null;
        items: Item[];
        adjustments: { id: number; number: string; typeLabel: string }[];
    };
    canApprove: boolean;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/*
 * الكميات المعدودة.
 *
 * بتبدأ فاضية مش بقيمة النظام: ورقة متملّية سلفًا بتخلّي اللي بيعد
 * يدوس «حفظ» من غير ما يعد، والجرد كله يبقى مالوش لازمة.
 */
const counted = reactive<Record<number, number | string>>(
    Object.fromEntries(
        props.count.items.map((item) => [item.id, item.countedQuantity ?? '']),
    ),
);

const saving = ref(false);
const approving = ref(false);

function differenceOf(item: Item): number | null {
    const value = counted[item.id];

    if (value === '' || value === null) {
        return null;
    }

    return Number(value) - item.systemQuantity;
}

/** عدد البنود اللي اتعدّت فعلاً */
const countedCount = computed(
    () =>
        props.count.items.filter(
            (item) => counted[item.id] !== '' && counted[item.id] !== null,
        ).length,
);

const differences = computed(() =>
    props.count.items.filter((item) => {
        const difference = differenceOf(item);

        return difference !== null && Math.abs(difference) > 1e-6;
    }),
);

function payload() {
    return {
        items: props.count.items.map((item) => ({
            id: item.id,
            counted_quantity:
                counted[item.id] === '' || counted[item.id] === null
                    ? null
                    : Number(counted[item.id]),
        })),
    };
}

function save() {
    saving.value = true;

    router.put(`/stock-counts/${props.count.id}`, payload(), {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
    });
}

function approve() {
    if (
        !window.confirm(
            `الاعتماد هيولّد أذون الفروق ويحرّك المخزون فعلاً (${differences.value.length} صنف). تمام؟`,
        )
    ) {
        return;
    }

    approving.value = true;

    // بنحفظ الأول عشان الاعتماد يشوف آخر الكميات
    router.put(`/stock-counts/${props.count.id}`, payload(), {
        preserveScroll: true,
        onSuccess: () => {
            router.post(
                `/stock-counts/${props.count.id}/approve`,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        approving.value = false;
                    },
                },
            );
        },
        onError: () => {
            approving.value = false;
        },
    });
}

function cancel() {
    if (!window.confirm('هتلغي ورقة الجرد. تمام؟')) {
        return;
    }

    router.post(
        `/stock-counts/${props.count.id}/cancel`,
        {},
        { preserveScroll: true },
    );
}

function openPrint() {
    window.open(`/stock-counts/${props.count.id}/print`, '_blank');
}
</script>

<template>
    <Head :title="count.number" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="`جرد ${count.number}`"
            :description="`${count.warehouse ?? '—'}${count.category ? ' · ' + count.category : ''}`"
        >
            <template #actions>
                <Button variant="outline" @click="openPrint">
                    <Printer class="size-4" />
                    طباعة الورقة
                </Button>
                <template v-if="count.isEditable">
                    <Button
                        variant="outline"
                        :disabled="saving"
                        data-test="save-counted"
                        @click="save"
                    >
                        <Spinner v-if="saving" class="size-4" />
                        <Save v-else class="size-4" />
                        حفظ المعدود
                    </Button>
                    <Button
                        v-if="canApprove"
                        :disabled="approving"
                        data-test="approve-count"
                        @click="approve"
                    >
                        <Spinner v-if="approving" class="size-4" />
                        <CheckCircle2 v-else class="size-4" />
                        اعتماد وتوليد الأذون
                    </Button>
                    <Button
                        variant="ghost"
                        data-test="cancel-count"
                        @click="cancel"
                    >
                        <Ban class="size-4" />
                        إلغاء
                    </Button>
                </template>
            </template>
        </PageHeader>

        <div class="grid gap-5 lg:grid-cols-4">
            <SectionCard title="الورقة">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">الحالة</dt>
                        <dd data-test="count-status">
                            {{ count.statusLabel }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">التاريخ</dt>
                        <dd class="tabular-nums">
                            {{ formatDate(count.date) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">فتحها</dt>
                        <dd>{{ count.createdBy ?? '—' }}</dd>
                    </div>
                </dl>
            </SectionCard>

            <SectionCard title="التقدّم">
                <p class="text-2xl font-bold tabular-nums">
                    {{ formatNumber(countedCount) }} /
                    {{ formatNumber(count.items.length) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">صنف اتعد</p>
            </SectionCard>

            <SectionCard title="الفروق">
                <p
                    class="text-2xl font-bold tabular-nums"
                    data-test="difference-count"
                >
                    {{ formatNumber(differences.length) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    صنف فيه فرق — الاعتماد هيولّد له إذن
                </p>
            </SectionCard>

            <SectionCard title="الأذون">
                <div v-if="count.adjustments.length" class="space-y-1 text-sm">
                    <Link
                        v-for="adjustment in count.adjustments"
                        :key="adjustment.id"
                        :href="`/stock-adjustments/${adjustment.id}`"
                        class="block hover:underline"
                        :data-test="`count-adjustment-${adjustment.number}`"
                    >
                        <span class="tabular-nums">{{
                            adjustment.number
                        }}</span>
                        <span class="text-xs text-muted-foreground">
                            — {{ adjustment.typeLabel }}
                        </span>
                    </Link>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    هتتولّد بعد الاعتماد.
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
                                رصيد النظام
                            </th>
                            <th class="w-36 px-5 py-2.5 text-start font-medium">
                                المعدود
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الفرق
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in count.items" :key="item.id">
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ item.sku ?? '—' }}
                            </td>
                            <td class="px-5 py-3">{{ item.name ?? '—' }}</td>
                            <td class="px-5 py-3 tabular-nums">
                                {{
                                    formatQuantity(
                                        item.systemQuantity,
                                        item.unit ?? undefined,
                                    )
                                }}
                            </td>
                            <td class="px-5 py-3">
                                <Input
                                    v-if="count.isEditable"
                                    v-model="counted[item.id]"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    dir="ltr"
                                    class="h-8 text-start"
                                    placeholder="—"
                                    :data-test="`counted-${item.id}`"
                                />
                                <span v-else class="tabular-nums">
                                    {{
                                        item.countedQuantity === null
                                            ? '—'
                                            : formatQuantity(
                                                  item.countedQuantity,
                                              )
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 font-semibold tabular-nums"
                                :class="{
                                    'text-status-good':
                                        (differenceOf(item) ?? 0) > 0,
                                    'text-status-critical':
                                        (differenceOf(item) ?? 0) < 0,
                                }"
                                :data-test="`difference-${item.id}`"
                            >
                                {{
                                    differenceOf(item) === null
                                        ? '—'
                                        : formatQuantity(differenceOf(item)!)
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </SectionCard>
    </div>
</template>
