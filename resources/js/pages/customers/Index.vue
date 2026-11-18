<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2, Users } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAbilities } from '@/composables/useAbilities';
import { formatMoney, formatNumber, formatPercent } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    phone: string | null;
    city: string | null;
    address: string | null;
    price_tier: string;
    sales_rep_id: number | null;
    sales_rep?: { id: number; name: string } | null;
    credit_limit: string | number | null;
    opening_balance: string | number;
    notes: string | null;
    is_active: boolean;
    balance: string | number;
};

const props = defineProps<{
    customers: { data: Row[] } & PaginatorMeta;
    /** في تجار أصلاً؟ بيفرّق بين «لسه مافيش» و«البحث مش جايب نتيجة» */
    hasAny: boolean;
    governorates: string[];
    reps: { id: number; name: string; region: string | null }[];
    filters: { search?: string; city?: string; status?: string };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const { manageMasterData } = useAbilities();

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

/** null = بنضيف تاجر جديد، وغير كده بنعدّل التاجر ده */
const editing = ref<Row | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    city: '',
    phone: '',
    address: '',
    price_tier: 'sale',
    sales_rep_id: '' as number | '',
    credit_limit: '' as number | string,
    opening_balance: 0 as number | string,
    notes: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        city: '',
        phone: '',
        address: '',
        price_tier: 'sale',
        sales_rep_id: '',
        credit_limit: '',
        opening_balance: 0,
        notes: '',
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(row: Row) {
    editing.value = row;
    form.defaults({
        name: row.name,
        city: row.city ?? '',
        phone: row.phone ?? '',
        address: row.address ?? '',
        price_tier: row.price_tier,
        sales_rep_id: row.sales_rep_id ?? '',
        credit_limit: row.credit_limit === null ? '' : Number(row.credit_limit),
        opening_balance: Number(row.opening_balance ?? 0),
        notes: row.notes ?? '',
        is_active: row.is_active,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    if (editing.value) {
        form.put(`/customers/${editing.value.id}`, options);
    } else {
        form.post('/customers', options);
    }
}

function remove(row: Row) {
    if (!window.confirm(`متأكد إنك عايز تحذف تاجر «${row.name}»؟`)) {
        return;
    }

    router.delete(`/customers/${row.id}`, { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="التجار" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="التجار"
            :description="`${formatNumber(customers.total)} تاجر مسجّل`"
        >
            <template #actions>
                <Button
                    v-if="manageMasterData"
                    data-test="customer-create"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    تاجر جديد
                </Button>
            </template>
        </PageHeader>

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
                                المندوب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                حد المديونية
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الرصيد
                            </th>
                            <th
                                v-if="manageMasterData"
                                class="px-5 py-2.5 text-start font-medium"
                            >
                                <span class="sr-only">إجراءات</span>
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
                                <span
                                    v-if="!row.is_active"
                                    class="ms-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    موقوف
                                </span>
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
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.sales_rep?.name ?? '—' }}
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
                            <td v-if="manageMasterData" class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        :aria-label="`تعديل ${row.name}`"
                                        :data-test="`customer-edit-${row.id}`"
                                        @click="openEdit(row)"
                                    >
                                        <Pencil class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                        :aria-label="`حذف ${row.name}`"
                                        @click="remove(row)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
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
            >
                <Button
                    v-if="manageMasterData"
                    data-test="customer-create-empty"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    أضف أول تاجر
                </Button>
            </EmptyState>

            <Pagination :meta="customers" />
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل التاجر' : 'تاجر جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        الاسم وشريحة السعر هم المطلوب — الشريحة بتحدّد السعر
                        اللي بيتحط في فاتورة البيع تلقائيًا.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="customer-name">اسم التاجر</Label>
                            <Input
                                id="customer-name"
                                v-model="form.name"
                                placeholder="محمد عبد الله للتجارة"
                                autocomplete="off"
                                data-test="customer-name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer-city">
                                المحافظة
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="customer-city"
                                v-model="form.city"
                                placeholder="الدقهلية"
                                list="governorate-options"
                                autocomplete="off"
                                data-test="customer-city-input"
                            />
                            <datalist id="governorate-options">
                                <option
                                    v-for="gov in governorates"
                                    :key="gov"
                                    :value="gov"
                                />
                            </datalist>
                            <InputError :message="form.errors.city" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer-phone">
                                التليفون
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="customer-phone"
                                v-model="form.phone"
                                dir="ltr"
                                class="text-start"
                                placeholder="01012345678"
                                autocomplete="off"
                                data-test="customer-phone"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer-tier">شريحة السعر</Label>
                            <select
                                id="customer-tier"
                                v-model="form.price_tier"
                                :class="SELECT_CLASS"
                                data-test="customer-tier"
                            >
                                <option value="sale">تجزئة</option>
                                <option value="wholesale">جملة الجملة</option>
                            </select>
                            <InputError :message="form.errors.price_tier" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer-rep">
                                المندوب
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <select
                                id="customer-rep"
                                v-model="form.sales_rep_id"
                                :class="SELECT_CLASS"
                                data-test="customer-rep"
                            >
                                <option value="">بدون مندوب</option>
                                <option
                                    v-for="rep in reps"
                                    :key="rep.id"
                                    :value="rep.id"
                                >
                                    {{ rep.name
                                    }}{{ rep.region ? ` — ${rep.region}` : '' }}
                                </option>
                            </select>
                            <InputError :message="form.errors.sales_rep_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer-credit">
                                حد المديونية
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="customer-credit"
                                v-model="form.credit_limit"
                                type="number"
                                step="0.01"
                                min="0"
                                dir="ltr"
                                class="text-start"
                                placeholder="سيبه فاضي = بدون حد"
                                data-test="customer-credit"
                            />
                            <InputError :message="form.errors.credit_limit" />
                        </div>

                        <div class="space-y-2">
                            <Label for="customer-opening">رصيد افتتاحي</Label>
                            <Input
                                id="customer-opening"
                                v-model="form.opening_balance"
                                type="number"
                                step="0.01"
                                dir="ltr"
                                class="text-start"
                                data-test="customer-opening"
                            />
                            <p class="text-xs text-muted-foreground">
                                اللي على التاجر قبل ما تبدأ على النظام.
                            </p>
                            <InputError
                                :message="form.errors.opening_balance"
                            />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="customer-address">
                                العنوان
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="customer-address"
                                v-model="form.address"
                                autocomplete="off"
                                data-test="customer-address"
                            />
                            <InputError :message="form.errors.address" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="customer-notes">
                                ملاحظات
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="customer-notes"
                                v-model="form.notes"
                                autocomplete="off"
                                data-test="customer-notes"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="customer-active"
                            v-model="form.is_active"
                            data-test="customer-active"
                        />
                        <Label for="customer-active" class="font-normal">
                            التاجر شغّال ويظهر في فواتير البيع
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="customer-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة التاجر' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
