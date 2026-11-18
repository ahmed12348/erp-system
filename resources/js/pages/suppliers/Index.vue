<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Truck } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
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
import { formatMoney, formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    country: string | null;
    phone: string | null;
    currency: string;
    openingBalance: number;
    notes: string | null;
    isActive: boolean;
    purchases: number;
    paid: number;
    balance: number;
    purchasesEgp: number;
    invoiceCount: number;
};

const props = defineProps<{
    suppliers: Row[];
    currencies: string[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const { manageMasterData } = useAbilities();

/** null = بنضيف مورد جديد، وغير كده بنعدّل المورد ده */
const editing = ref<Row | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    country: '',
    currency: 'USD',
    phone: '',
    opening_balance: 0 as number | string,
    notes: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        country: '',
        currency: props.currencies.includes('USD')
            ? 'USD'
            : (props.currencies[0] ?? 'EGP'),
        phone: '',
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
        country: row.country ?? '',
        currency: row.currency,
        phone: row.phone ?? '',
        opening_balance: row.openingBalance,
        notes: row.notes ?? '',
        is_active: row.isActive,
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
        form.put(`/suppliers/${editing.value.id}`, options);
    } else {
        form.post('/suppliers', options);
    }
}

function remove(row: Row) {
    if (!window.confirm(`متأكد إنك عايز تحذف مورد «${row.name}»؟`)) {
        return;
    }

    router.delete(`/suppliers/${row.id}`, { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="الموردون" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="الموردون"
            description="أرقام كل مورد معروضة بعملته — التحويل للجنيه في عمود منفصل"
        >
            <template #actions>
                <Button
                    v-if="manageMasterData"
                    data-test="supplier-create"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    مورد جديد
                </Button>
            </template>
        </PageHeader>

        <SectionCard flush>
            <div v-if="suppliers.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المورد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                البلد
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التليفون
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                العملة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                عدد الفواتير
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                إجمالي المشتريات
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المدفوع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المستحق
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المشتريات بالجنيه
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
                            v-for="row in suppliers"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="`/suppliers/${row.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ row.name }}
                                </Link>
                                <span
                                    v-if="!row.isActive"
                                    class="ms-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    موقوف
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.country ?? '—' }}
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                <span dir="ltr">{{ row.phone ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ row.currency }}
                                </span>
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ formatNumber(row.invoiceCount) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{
                                    formatMoney(row.purchases, row.currency, 2)
                                }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-status-good tabular-nums"
                            >
                                {{ formatMoney(row.paid, row.currency, 2) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                                :class="
                                    row.balance > 0
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ formatMoney(row.balance, row.currency, 2) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatMoney(row.purchasesEgp) }}
                            </td>
                            <td v-if="manageMasterData" class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        :aria-label="`تعديل ${row.name}`"
                                        :data-test="`supplier-edit-${row.id}`"
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
                v-else
                large
                :icon="Truck"
                title="لسه مافيش موردين"
                description="كل مورد بيتسجّل بعملة تعامله، وأرصدته بتفضل بعملته — التحويل للجنيه بيحصل في الفواتير بسعر الصرف."
            >
                <Button
                    v-if="manageMasterData"
                    data-test="supplier-create-empty"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    أضف أول مورد
                </Button>
            </EmptyState>
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل المورد' : 'مورد جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        الاسم والعملة هم المطلوب — العملة بتحدّد عملة فواتير
                        الشراء وكشف الحساب.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="supplier-name">اسم المورد</Label>
                            <Input
                                id="supplier-name"
                                v-model="form.name"
                                placeholder="شركة الأناضول للتجارة"
                                autocomplete="off"
                                data-test="supplier-name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="supplier-country">
                                البلد
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="supplier-country"
                                v-model="form.country"
                                placeholder="تركيا"
                                autocomplete="off"
                                data-test="supplier-country"
                            />
                            <InputError :message="form.errors.country" />
                        </div>

                        <div class="space-y-2">
                            <Label for="supplier-currency">عملة التعامل</Label>
                            <select
                                id="supplier-currency"
                                v-model="form.currency"
                                :class="SELECT_CLASS"
                                data-test="supplier-currency"
                            >
                                <option
                                    v-for="c in currencies"
                                    :key="c"
                                    :value="c"
                                >
                                    {{ c }}
                                </option>
                            </select>
                            <InputError :message="form.errors.currency" />
                        </div>

                        <div class="space-y-2">
                            <Label for="supplier-phone">
                                جهة الاتصال
                                <span class="text-muted-foreground"
                                    >(تليفون أو واتساب)</span
                                >
                            </Label>
                            <Input
                                id="supplier-phone"
                                v-model="form.phone"
                                dir="ltr"
                                class="text-start"
                                placeholder="+90 532 000 0000"
                                autocomplete="off"
                                data-test="supplier-phone"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="supplier-opening">
                                رصيد افتتاحي ({{ form.currency }})
                            </Label>
                            <Input
                                id="supplier-opening"
                                v-model="form.opening_balance"
                                type="number"
                                step="0.01"
                                dir="ltr"
                                class="text-start"
                                data-test="supplier-opening"
                            />
                            <p class="text-xs text-muted-foreground">
                                اللي مستحق للمورد قبل ما تبدأ على النظام.
                            </p>
                            <InputError
                                :message="form.errors.opening_balance"
                            />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="supplier-notes">
                                ملاحظات
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="supplier-notes"
                                v-model="form.notes"
                                autocomplete="off"
                                data-test="supplier-notes"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="supplier-active"
                            v-model="form.is_active"
                            data-test="supplier-active"
                        />
                        <Label for="supplier-active" class="font-normal">
                            المورد شغّال ويظهر في فواتير الشراء
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="supplier-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة المورد' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
