<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Warehouse } from '@lucide/vue';
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
import { formatMoney, formatNumber, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    location: string | null;
    isActive: boolean;
    items: number;
    units: number;
    value: number;
    invoices: number;
};

defineProps<{
    warehouses: Row[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/** null = بنضيف مخزن جديد، وغير كده بنعدّل المخزن ده */
const editing = ref<Row | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    location: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.defaults({ name: '', location: '', is_active: true });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(row: Row) {
    editing.value = row;
    form.defaults({
        name: row.name,
        location: row.location ?? '',
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
        form.put(`/warehouses/${editing.value.id}`, options);
    } else {
        form.post('/warehouses', options);
    }
}

function remove(row: Row) {
    if (!window.confirm(`متأكد إنك عايز تحذف مخزن «${row.name}»؟`)) {
        return;
    }

    router.delete(`/warehouses/${row.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="المخازن" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="المخازن"
            description="أماكن تخزين البضاعة — كل حركة شراء أو بيع بتتسجّل على مخزن"
        >
            <template #actions>
                <Button data-test="warehouse-create" @click="openCreate">
                    <Plus class="size-4" />
                    مخزن جديد
                </Button>
            </template>
        </PageHeader>

        <SectionCard title="كل المخازن" flush>
            <div
                v-if="warehouses.length"
                class="scrollbar-thin overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المخزن
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                العنوان
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                أصناف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الوحدات
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                قيمة المخزون
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الحالة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                <span class="sr-only">إجراءات</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in warehouses"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium">
                                {{ row.name }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.location ?? '—' }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatNumber(row.items) }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatQuantity(row.units) }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.value) }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.isActive
                                            ? 'bg-status-good/10 text-status-good'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ row.isActive ? 'شغّال' : 'موقوف' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        :aria-label="`تعديل ${row.name}`"
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
                :icon="Warehouse"
                title="لسه مافيش مخازن"
                description="المخزن هو أول حاجة تسجّلها — من غيره مش هتقدر تعمل فاتورة شراء أو بيع."
            >
                <Button data-test="warehouse-create-empty" @click="openCreate">
                    <Plus class="size-4" />
                    أضف أول مخزن
                </Button>
            </EmptyState>
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل المخزن' : 'مخزن جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        الاسم بس هو المطلوب — تقدر تضيف العنوان بعدين.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="warehouse-name">اسم المخزن</Label>
                        <Input
                            id="warehouse-name"
                            v-model="form.name"
                            placeholder="المخزن الرئيسي"
                            autocomplete="off"
                            data-test="warehouse-name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="warehouse-location">
                            العنوان
                            <span class="text-muted-foreground">(اختياري)</span>
                        </Label>
                        <Input
                            id="warehouse-location"
                            v-model="form.location"
                            placeholder="المنطقة الصناعية، العبور"
                            autocomplete="off"
                            data-test="warehouse-location"
                        />
                        <InputError :message="form.errors.location" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="warehouse-active"
                            v-model="form.is_active"
                            data-test="warehouse-active"
                        />
                        <Label for="warehouse-active" class="font-normal">
                            المخزن شغّال ويظهر في الفواتير
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="warehouse-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة المخزن' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
