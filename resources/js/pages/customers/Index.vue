<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Users } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Input } from '@/components/ui/input';
import { formatMoney, formatNumber, formatPercent } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    phone: string | null;
    city: string | null;
    price_tier: string;
    credit_limit: string | number | null;
    balance: string | number;
};

const props = defineProps<{
    customers: { data: Row[] } & PaginatorMeta;
    /** في تجار أصلاً؟ بيفرّق بين «لسه مافيش» و«البحث مش جايب نتيجة» */
    hasAny: boolean;
    governorates: string[];
    filters: { search?: string; city?: string; status?: string };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const search = ref(props.filters.search ?? '');
const city = ref(props.filters.city ?? '');
const status = ref(props.filters.status ?? '');

function apply() {
    router.get(
        '/customers',
        {
            search: search.value || undefined,
            city: city.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

// البحث بيتأخّر شوية عشان ما نبعتش طلب مع كل حرف
watchDebounced(search, apply, { debounce: 350 });

/** نسبة استهلاك حد المديونية — null لو التاجر مالوش حد */
function usage(row: Row): number | null {
    const limit = Number(row.credit_limit ?? 0);

    return limit > 0 ? Number(row.balance) / limit : null;
}
</script>

<template>
    <Head title="التجار" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="التجار"
            :description="`${formatNumber(customers.total)} تاجر مسجّل`"
        />

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute inset-y-0 end-3 my-auto size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="ابحث بالاسم أو التليفون"
                            class="w-56 pe-9"
                            data-test="customer-search"
                        />
                    </div>

                    <select
                        v-model="city"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="customer-city"
                        @change="apply"
                    >
                        <option value="">كل المحافظات</option>
                        <option
                            v-for="gov in governorates"
                            :key="gov"
                            :value="gov"
                        >
                            {{ gov }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="customer-status"
                        @change="apply"
                    >
                        <option value="">كل الحالات</option>
                        <option value="indebted">عليه مديونية</option>
                        <option value="over_limit">تعدّى حد المديونية</option>
                    </select>
                </div>
            </template>

            <div
                v-if="customers.data.length"
                class="scrollbar-thin overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاجر
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المحافظة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التليفون
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الشريحة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                حد المديونية
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الرصيد
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in customers.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="`/customers/${row.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ row.name }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.city ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                <span dir="ltr">{{ row.phone ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{
                                    row.price_tier === 'wholesale'
                                        ? 'جملة الجملة'
                                        : 'تجزئة'
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                <template v-if="row.credit_limit">
                                    {{ formatMoney(row.credit_limit) }}
                                    <span
                                        v-if="usage(row) !== null"
                                        class="ms-1 text-xs"
                                        :class="
                                            usage(row)! > 1
                                                ? 'font-semibold text-status-critical'
                                                : usage(row)! > 0.8
                                                  ? 'text-status-warning'
                                                  : 'text-muted-foreground'
                                        "
                                    >
                                        ({{ formatPercent(usage(row)!, 0) }})
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >بدون حد</span
                                >
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                                :class="
                                    Number(row.balance) > 0
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatMoney(row.balance) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else-if="hasAny"
                :icon="Users"
                title="مفيش تجار مطابقين للبحث"
                description="جرّب تغيّر كلمة البحث أو تشيل الفلاتر."
                :secondary="{ label: 'امسح الفلاتر', href: '/customers' }"
            />

            <EmptyState
                v-else
                large
                :icon="Users"
                title="لسه مافيش تجار"
                description="التجار بيتسجّلوا بحد ائتمان وشريحة سعر، وكل فاتورة بيع بتتقيّد على حسابهم أول بأول."
            />

            <Pagination :meta="customers" />
        </SectionCard>
    </div>
</template>
