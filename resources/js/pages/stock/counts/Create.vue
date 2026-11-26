<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ClipboardList } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    warehouses: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    today: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const form = useForm({
    warehouse_id: props.warehouses[0]?.id ?? ('' as number | ''),
    category_id: '' as number | '',
    date: props.today,
    notes: '',
});

function submit() {
    form.transform((data) => ({
        ...data,
        category_id: data.category_id === '' ? null : data.category_id,
    })).post('/stock-counts', { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="جرد جديد" />

    <form
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="جرد جديد"
            description="الورقة بتتجمّد عليها أرصدة النظام وقت الفتح — عشان أي بيعة بعد كده ما تبوّظش الفروق"
        />

        <SectionCard title="نطاق الجرد">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="warehouse_id">المخزن</Label>
                    <select
                        id="warehouse_id"
                        v-model="form.warehouse_id"
                        :class="SELECT_CLASS"
                        data-test="count-warehouse"
                    >
                        <option
                            v-for="w in warehouses"
                            :key="w.id"
                            :value="w.id"
                        >
                            {{ w.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.warehouse_id" />
                </div>

                <div class="space-y-2">
                    <Label for="category_id">التصنيف (اختياري)</Label>
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        :class="SELECT_CLASS"
                        data-test="count-category"
                    >
                        <option value="">كل الأصناف</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        سيبه فاضي عشان تجرد المخزن كله
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="date">التاريخ</Label>
                    <Input
                        id="date"
                        v-model="form.date"
                        type="date"
                        dir="ltr"
                        class="text-start"
                        data-test="count-date"
                    />
                    <InputError :message="form.errors.date" />
                </div>
            </div>

            <div class="mt-4 space-y-2">
                <Label for="notes">ملاحظات</Label>
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="2"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                ></textarea>
            </div>
        </SectionCard>

        <div class="flex justify-end">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="open-count"
            >
                <Spinner v-if="form.processing" class="size-4" />
                <ClipboardList v-else class="size-4" />
                افتح ورقة الجرد
            </Button>
        </div>
    </form>
</template>
