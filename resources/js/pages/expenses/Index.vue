<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Receipt, Search, Settings2 } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import DownloadMenu from '@/components/DownloadMenu.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAbilities } from '@/composables/useAbilities';
import { formatDateShort, formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    number: string;
    date: string;
    category: string | null;
    group: string | null;
    amount: number;
    method: string;
    paidTo: string | null;
    createdBy: string | null;
};

const props = defineProps<{
    expenses: { data: Row[] } & PaginatorMeta;
    total: number;
    hasAny: boolean;
    filters: Record<string, string | undefined>;
    tree: {
        id: number;
        name: string;
        children: { id: number; name: string }[];
    }[];
    categories: {
        id: number;
        name: string;
        group: string | null;
        code: string | null;
    }[];
    methods: { value: string; label: string }[];
    today: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/** شجرة التصنيفات للمدير بس */
const { manageUsers } = useAbilities();

const filters = reactive({
    search: props.filters.search ?? '',
    category_id: props.filters.category_id ?? '',
    method: props.filters.method ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function query(): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    ) as Record<string, string>;
}

function apply() {
    router.get('/expenses', query(), {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

watchDebounced(() => filters.search, apply, { debounce: 350 });

/** تقرير المصروفات بياخد نفس الفترة */
const exportHref = computed(() => {
    const params = new URLSearchParams({ report: 'expenses' });

    if (filters.from) params.set('from', filters.from);
    if (filters.to) params.set('to', filters.to);

    return `/exports/report?${params.toString()}`;
});

// ── تسجيل مصروف ──────────────────────────────────────────────────

const open = ref(false);

const form = useForm({
    expense_category_id: '' as number | '',
    date: props.today,
    amount: '' as number | string,
    method: 'cash',
    paid_to: '',
    notes: '',
    attachment: null as File | null,
});

function submit() {
    form.post('/expenses', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            form.date = props.today;
            open.value = false;
        },
    });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="المصروفات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="المصروفات"
            :description="`${formatNumber(expenses.total)} مصروف · ${formatMoney(total)} في الفترة`"
        >
            <template #actions>
                <DownloadMenu :href="exportHref" label="تحميل التقرير" />
                <Button v-if="manageUsers" variant="outline" as-child>
                    <Link href="/expenses/categories">
                        <Settings2 class="size-4" />
                        التصنيفات
                    </Link>
                </Button>
                <Button data-test="open-expense" @click="open = true">
                    <Plus class="size-4" />
                    مصروف جديد
                </Button>
            </template>
        </PageHeader>

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search
                            class="absolute start-2.5 top-2.5 size-4 text-muted-foreground"
                        />
                        <Input
                            v-model="filters.search"
                            class="h-9 w-48 ps-8"
                            placeholder="رقم المصروف أو المستفيد"
                            data-test="expense-search"
                        />
                    </div>
                    <select
                        v-model="filters.category_id"
                        :class="SELECT_CLASS"
                        class="w-auto"
                        data-test="filter-category"
                        @change="apply"
                    >
                        <option value="">كل التصنيفات</option>
                        <optgroup
                            v-for="group in tree"
                            :key="group.id"
                            :label="group.name"
                        >
                            <option :value="group.id">
                                كل {{ group.name }}
                            </option>
                            <option
                                v-for="child in group.children"
                                :key="child.id"
                                :value="child.id"
                            >
                                {{ child.name }}
                            </option>
                        </optgroup>
                    </select>
                    <Input
                        v-model="filters.from"
                        type="date"
                        class="h-9 w-38"
                        aria-label="من تاريخ"
                        @change="apply"
                    />
                    <Input
                        v-model="filters.to"
                        type="date"
                        class="h-9 w-38"
                        aria-label="إلى تاريخ"
                        @change="apply"
                    />
                </div>
            </template>

            <EmptyState
                v-if="!hasAny"
                :icon="Receipt"
                title="مفيش مصروفات لسه"
                description="سجّل الإيجار والمرتبات والكهرباء — بتطلع من الخزينة وبتدخل في صافي الربح على اللوحة."
            />

            <EmptyState
                v-else-if="!expenses.data.length"
                :icon="Search"
                title="الفلتر مش جايب نتيجة"
                description="غيّر الفلتر أو امسحه."
            />

            <div v-else class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                رقم المصروف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التصنيف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المستفيد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                طريقة الدفع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المبلغ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in expenses.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td
                                class="px-5 py-3 font-medium whitespace-nowrap tabular-nums"
                                :data-test="`expense-${row.number}`"
                            >
                                {{ row.number }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatDateShort(row.date) }}
                            </td>
                            <td class="px-5 py-3">
                                {{ row.category ?? '—' }}
                                <span
                                    v-if="row.group"
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{ row.group }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.paidTo ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.method }}
                            </td>
                            <td class="px-5 py-3 font-semibold tabular-nums">
                                {{ formatMoney(row.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination v-if="expenses.data.length" :meta="expenses" />
        </SectionCard>
    </div>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>مصروف جديد</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="expense_category_id">بند المصروف</Label>
                    <select
                        id="expense_category_id"
                        v-model="form.expense_category_id"
                        :class="SELECT_CLASS"
                        data-test="expense-category"
                    >
                        <option value="" disabled>اختار البند</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.group }} ← {{ c.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.expense_category_id" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="amount">المبلغ</Label>
                        <Input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            dir="ltr"
                            class="text-start"
                            data-test="expense-amount"
                        />
                        <InputError :message="form.errors.amount" />
                    </div>

                    <div class="space-y-2">
                        <Label for="date">التاريخ</Label>
                        <Input
                            id="date"
                            v-model="form.date"
                            type="date"
                            dir="ltr"
                            class="text-start"
                            data-test="expense-date"
                        />
                        <InputError :message="form.errors.date" />
                    </div>

                    <div class="space-y-2">
                        <Label for="method">طريقة الدفع</Label>
                        <select
                            id="method"
                            v-model="form.method"
                            :class="SELECT_CLASS"
                            data-test="expense-method"
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

                    <div class="space-y-2">
                        <Label for="paid_to">المستفيد</Label>
                        <Input
                            id="paid_to"
                            v-model="form.paid_to"
                            placeholder="اسم اللي اتدفعله"
                            data-test="expense-paid-to"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="notes">ملاحظات</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                    ></textarea>
                </div>

                <div class="space-y-2">
                    <Label for="attachment">الإيصال (اختياري)</Label>
                    <input
                        id="attachment"
                        type="file"
                        class="w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm"
                        data-test="expense-attachment"
                        @change="
                            form.attachment =
                                ($event.target as HTMLInputElement)
                                    .files?.[0] ?? null
                        "
                    />
                    <InputError :message="form.errors.attachment" />
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                    data-test="save-expense"
                >
                    <Spinner v-if="form.processing" class="size-4" />
                    سجّل واخصم من الخزينة
                </Button>
            </form>
        </DialogContent>
    </Dialog>
</template>
