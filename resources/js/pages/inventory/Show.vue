<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { History } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { formatDateShort, formatMoney, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    product: {
        id: number;
        sku: string;
        name: string;
        unit: string;
        minStock: number;
        cost: number;
        balance: number;
    };
    rows: {
        id: number;
        date: string;
        type: string;
        warehouse: string | null;
        quantity: number;
        unitCost: number | null;
        notes: string | null;
        balance: number;
        link: string | null;
        reference: string | null;
    }[];
    warehouses: { id: number; name: string }[];
    filters: { warehouse_id: number | null };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const warehouseId = ref(String(props.filters.warehouse_id ?? ''));

function apply() {
    router.get(
        `/inventory/${props.product.id}`,
        warehouseId.value ? { warehouse_id: warehouseId.value } : {},
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

const TYPE_LABELS: Record<string, string> = {
    in: 'وارد',
    out: 'منصرف',
    adjust: 'تسوية',
    transfer: 'تحويل',
};
</script>

<template>
    <Head :title="product.name" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :title="product.name"
            :description="`كود الصنف ${product.sku}`"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">الرصيد الحالي</p>
                <p
                    class="mt-2 text-xl font-bold tabular-nums"
                    :class="
                        product.balance < product.minStock
                            ? 'text-status-critical'
                            : ''
                    "
                    data-test="product-balance"
                >
                    {{ formatQuantity(product.balance, product.unit) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">حد الطلب</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatQuantity(product.minStock, product.unit) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">آخر تكلفة واصلة</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatMoney(product.cost) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-border bg-card p-5 shadow-soft"
            >
                <p class="text-sm text-muted-foreground">قيمة الرصيد</p>
                <p class="mt-2 text-xl font-bold tabular-nums">
                    {{ formatMoney(product.balance * product.cost) }}
                </p>
            </div>
        </div>

        <SectionCard title="حركة الصنف" flush>
            <template #actions>
                <select
                    v-model="warehouseId"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    data-test="movement-warehouse"
                    @change="apply"
                >
                    <option value="">كل المخازن</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        {{ w.name }}
                    </option>
                </select>
            </template>

            <div v-if="rows.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                النوع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المرجع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الكمية
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التكلفة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الرصيد بعدها
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in rows"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.quantity >= 0
                                            ? 'bg-status-good/10 text-status-good'
                                            : 'bg-status-warning/10 text-status-warning'
                                    "
                                >
                                    {{ TYPE_LABELS[row.type] ?? row.type }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.warehouse ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                <Link
                                    v-if="row.link"
                                    :href="row.link"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ row.reference }}
                                </Link>
                                <span v-else class="text-muted-foreground">
                                    {{ row.reference ?? '—' }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                                :class="
                                    row.quantity >= 0
                                        ? 'text-status-good'
                                        : 'text-status-warning'
                                "
                            >
                                <span dir="ltr">
                                    {{ row.quantity >= 0 ? '+' : '−'
                                    }}{{
                                        formatQuantity(Math.abs(row.quantity))
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{
                                    row.unitCost
                                        ? formatMoney(row.unitCost)
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatQuantity(row.balance) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="History"
                title="مفيش حركة على الصنف ده"
                description="الصنف لسه مادخلش ولا خرج من أي مخزن."
            />
        </SectionCard>
    </div>
</template>
