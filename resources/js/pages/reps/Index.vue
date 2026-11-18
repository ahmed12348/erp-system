<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, UserRound } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
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

type Rep = {
    id: number;
    name: string;
    phone: string | null;
    region: string | null;
    commissionRate: number;
    isActive: boolean;
    customers: number;
    invoices: number;
    netSales: number;
    collected: number;
    outstanding: number;
    commission: number;
    collectionRate: number | null;
};

const props = defineProps<{
    reps: Rep[];
    filters: { from?: string; to?: string };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/reps',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

const totals = computed(() => ({
    netSales: props.reps.reduce((s, r) => s + r.netSales, 0),
    collected: props.reps.reduce((s, r) => s + r.collected, 0),
    outstanding: props.reps.reduce((s, r) => s + r.outstanding, 0),
    commission: props.reps.reduce((s, r) => s + r.commission, 0),
}));

const { manageMasterData } = useAbilities();

/** null = بنضيف مندوب جديد، وغير كده بنعدّل المندوب ده */
const editing = ref<Rep | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    region: '',
    phone: '',
    commission_rate: 0 as number | string,
    notes: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        region: '',
        phone: '',
        commission_rate: 0,
        notes: '',
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(rep: Rep) {
    editing.value = rep;
    form.defaults({
        name: rep.name,
        region: rep.region ?? '',
        phone: rep.phone ?? '',
        commission_rate: rep.commissionRate,
        notes: '',
        is_active: rep.isActive,
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
        form.put(`/reps/${editing.value.id}`, options);
    } else {
        form.post('/reps', options);
    }
}

function remove(rep: Rep) {
    if (!window.confirm(`متأكد إنك عايز تحذف مندوب «${rep.name}»؟`)) {
        return;
    }

    router.delete(`/reps/${rep.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="المندوبين" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="مندوبي المبيعات"
            description="العمولة بتتحسب على المحصّل مش على المبيعات"
        >
            <template #actions>
                <Button
                    v-if="manageMasterData"
                    data-test="rep-create"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    مندوب جديد
                </Button>
            </template>
        </PageHeader>

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="filters.from"
                        type="date"
                        class="h-9 w-38"
                        aria-label="من تاريخ"
                        data-test="reps-from"
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

            <div v-if="reps.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المندوب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المنطقة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التجار
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الفواتير
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                صافي المبيعات
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المحصّل
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                نسبة التحصيل
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المستحق على تجاره
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                العمولة
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
                            v-for="rep in reps"
                            :key="rep.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="`/reps/${rep.id}`"
                                    class="font-medium hover:text-primary hover:underline"
                                >
                                    {{ rep.name }}
                                </Link>
                                <span
                                    v-if="!rep.isActive"
                                    class="ms-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    موقوف
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ rep.region ?? '—' }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(rep.customers) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(rep.invoices) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(rep.netSales) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-status-good tabular-nums"
                            >
                                {{ formatMoney(rep.collected) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                <template v-if="rep.collectionRate !== null">
                                    <span
                                        :class="
                                            rep.collectionRate >= 70
                                                ? 'text-status-good'
                                                : rep.collectionRate >= 45
                                                  ? 'text-status-warning'
                                                  : 'text-status-critical'
                                        "
                                    >
                                        {{
                                            formatNumber(rep.collectionRate, 1)
                                        }}%
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(rep.outstanding) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap tabular-nums"
                            >
                                <span class="font-semibold">
                                    {{ formatMoney(rep.commission) }}
                                </span>
                                <span
                                    class="ms-1 text-xs text-muted-foreground"
                                >
                                    ({{ formatNumber(rep.commissionRate, 2) }}%)
                                </span>
                            </td>
                            <td v-if="manageMasterData" class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        :aria-label="`تعديل ${rep.name}`"
                                        :data-test="`rep-edit-${rep.id}`"
                                        @click="openEdit(rep)"
                                    >
                                        <Pencil class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                        :aria-label="`حذف ${rep.name}`"
                                        @click="remove(rep)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-border bg-muted/40">
                        <tr class="font-bold">
                            <td colspan="4" class="px-5 py-3">الإجمالي</td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(totals.netSales) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(totals.collected) }}
                            </td>
                            <td class="px-5 py-3"></td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                data-test="reps-outstanding"
                            >
                                {{ formatMoney(totals.outstanding) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatMoney(totals.commission) }}
                            </td>
                            <td v-if="manageMasterData" class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <EmptyState
                v-else
                large
                :icon="UserRound"
                title="لسه مافيش مندوبين"
                description="أول ما تضيف مندوبين وتربطهم بالتجار، هتشوف هنا مبيعات كل مندوب وتحصيلاته وعمولته — العمولة بتتحسب على المحصّل مش على المبيعات."
            >
                <Button
                    v-if="manageMasterData"
                    data-test="rep-create-empty"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    أضف أول مندوب
                </Button>
            </EmptyState>
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل المندوب' : 'مندوب جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        نسبة العمولة بتتحسب على المبالغ اللي المندوب حصّلها
                        فعلاً، مش على مبيعاته.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="rep-name">اسم المندوب</Label>
                            <Input
                                id="rep-name"
                                v-model="form.name"
                                placeholder="كريم سعيد"
                                autocomplete="off"
                                data-test="rep-name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rep-region">
                                المنطقة
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="rep-region"
                                v-model="form.region"
                                placeholder="الدلتا"
                                autocomplete="off"
                                data-test="rep-region"
                            />
                            <InputError :message="form.errors.region" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rep-phone">
                                التليفون
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="rep-phone"
                                v-model="form.phone"
                                dir="ltr"
                                class="text-start"
                                placeholder="01012345678"
                                autocomplete="off"
                                data-test="rep-phone"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="rep-commission">نسبة العمولة %</Label>
                            <Input
                                id="rep-commission"
                                v-model="form.commission_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                dir="ltr"
                                class="text-start"
                                data-test="rep-commission"
                            />
                            <InputError
                                :message="form.errors.commission_rate"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="rep-notes">
                                ملاحظات
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="rep-notes"
                                v-model="form.notes"
                                autocomplete="off"
                                data-test="rep-notes"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="rep-active"
                            v-model="form.is_active"
                            data-test="rep-active"
                        />
                        <Label for="rep-active" class="font-normal">
                            المندوب شغّال ويظهر في قوايم التجار والفواتير
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="rep-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة المندوب' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
