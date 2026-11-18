<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
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
import { formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    isActive: boolean;
    products: number;
};

defineProps<{
    categories: Row[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const { manageMasterData } = useAbilities();

/** null = بنضيف تصنيف جديد، وغير كده بنعدّل التصنيف ده */
const editing = ref<Row | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.defaults({ name: '', is_active: true });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(row: Row) {
    editing.value = row;
    form.defaults({ name: row.name, is_active: row.isActive });
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
        form.put(`/categories/${editing.value.id}`, options);
    } else {
        form.post('/categories', options);
    }
}

function remove(row: Row) {
    if (!window.confirm(`متأكد إنك عايز تحذف تصنيف «${row.name}»؟`)) {
        return;
    }

    router.delete(`/categories/${row.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="التصنيفات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="التصنيفات"
            description="تجميعة الأصناف في المخزون والتقارير — مالهاش أي أثر محاسبي"
        >
            <template #actions>
                <Button
                    v-if="manageMasterData"
                    data-test="category-create"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    تصنيف جديد
                </Button>
            </template>
        </PageHeader>

        <SectionCard title="كل التصنيفات" flush>
            <div
                v-if="categories.length"
                class="scrollbar-thin overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التصنيف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                عدد الأصناف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الحالة
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
                            v-for="row in categories"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium">
                                {{ row.name }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                <Link
                                    v-if="row.products"
                                    :href="`/products?category_id=${row.id}`"
                                    class="hover:text-primary hover:underline"
                                >
                                    {{ formatNumber(row.products) }}
                                </Link>
                                <span v-else class="text-muted-foreground"
                                    >0</span
                                >
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
                            <td v-if="manageMasterData" class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        :aria-label="`تعديل ${row.name}`"
                                        :data-test="`category-edit-${row.id}`"
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
                :icon="Tags"
                title="لسه مافيش تصنيفات"
                description="التصنيف اختياري — بس بيخلي المخزون والتقارير أسهل في القراءة لما الأصناف تكتر."
            >
                <Button
                    v-if="manageMasterData"
                    data-test="category-create-empty"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    أضف أول تصنيف
                </Button>
            </EmptyState>
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل التصنيف' : 'تصنيف جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        الاسم بس هو المطلوب.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="category-name">اسم التصنيف</Label>
                        <Input
                            id="category-name"
                            v-model="form.name"
                            placeholder="زيوت ومنظفات"
                            autocomplete="off"
                            data-test="category-name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="category-active"
                            v-model="form.is_active"
                            data-test="category-active"
                        />
                        <Label for="category-active" class="font-normal">
                            التصنيف شغّال ويظهر في قوايم الأصناف
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="category-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة التصنيف' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
