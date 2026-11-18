<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
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
import { formatMoney, formatNumber, formatQuantity } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    sku: string;
    name: string;
    unit: string;
    categoryId: number | null;
    category: string | null;
    purchasePrice: number;
    salePrice: number;
    wholesalePrice: number | null;
    minStock: number;
    isActive: boolean;
    stock: number;
};

const props = defineProps<{
    products: { data: Row[] } & PaginatorMeta;
    categories: { id: number; name: string }[];
    units: string[];
    /** كود مقترح للصنف الجديد — P-0001، P-0002 … */
    suggestedSku: string;
    /** في أصناف أصلاً؟ بيفرّق بين «لسه مافيش» و«البحث مش جايب نتيجة» */
    hasAny: boolean;
    filters: { search?: string; category_id?: string; status?: string };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const { manageMasterData } = useAbilities();

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const status = ref(props.filters.status ?? '');

function apply() {
    router.get(
        '/products',
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

// البحث بيتأخّر شوية عشان ما نبعتش طلب مع كل حرف
watchDebounced(search, apply, { debounce: 350 });

/** null = بنضيف صنف جديد، وغير كده بنعدّل الصنف ده */
const editing = ref<Row | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    sku: '',
    unit: props.units[0] ?? 'قطعة',
    category_id: '' as number | '',
    purchase_price: 0 as number | string,
    sale_price: '' as number | string,
    wholesale_price: '' as number | string,
    min_stock: 0 as number | string,
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        sku: props.suggestedSku,
        unit: props.units[0] ?? 'قطعة',
        category_id: '',
        purchase_price: 0,
        sale_price: '',
        wholesale_price: '',
        min_stock: 0,
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
        sku: row.sku,
        unit: row.unit,
        category_id: row.categoryId ?? '',
        purchase_price: row.purchasePrice,
        sale_price: row.salePrice,
        wholesale_price: row.wholesalePrice ?? '',
        min_stock: row.minStock,
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
        form.put(`/products/${editing.value.id}`, options);
    } else {
        form.post('/products', options);
    }
}

function remove(row: Row) {
    if (!window.confirm(`متأكد إنك عايز تحذف صنف «${row.name}»؟`)) {
        return;
    }

    router.delete(`/products/${row.id}`, { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="الأصناف" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="الأصناف"
            :description="`${formatNumber(products.total)} صنف في الكتالوج — الأسعار والوحدات وحد الطلب`"
        >
            <template #actions>
                <Button
                    v-if="manageMasterData"
                    data-test="product-create"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    صنف جديد
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
                            placeholder="ابحث بالاسم أو الكود"
                            class="w-56 pe-9"
                            data-test="product-search"
                        />
                    </div>

                    <select
                        v-model="categoryId"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="product-category-filter"
                        @change="apply"
                    >
                        <option value="">كل التصنيفات</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        data-test="product-status-filter"
                        @change="apply"
                    >
                        <option value="">كل الحالات</option>
                        <option value="active">شغّال</option>
                        <option value="inactive">موقوف</option>
                    </select>
                </div>
            </template>

            <div
                v-if="products.data.length"
                class="scrollbar-thin overflow-x-auto"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الصنف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الكود
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التصنيف
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الوحدة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                سعر البيع
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                سعر الجملة
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                حد الطلب
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الرصيد
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
                            v-for="row in products.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium">
                                {{ row.name }}
                            </td>
                            <td
                                class="px-5 py-3 text-muted-foreground tabular-nums"
                            >
                                <span dir="ltr">{{ row.sku }}</span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.category ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ row.unit }}
                            </td>
                            <td
                                class="px-5 py-3 font-semibold whitespace-nowrap tabular-nums"
                            >
                                {{ formatMoney(row.salePrice) }}
                            </td>
                            <td
                                class="px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{
                                    row.wholesalePrice === null
                                        ? '—'
                                        : formatMoney(row.wholesalePrice)
                                }}
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ formatQuantity(row.minStock) }}
                            </td>
                            <td
                                class="px-5 py-3 tabular-nums"
                                :class="
                                    row.minStock > 0 &&
                                    row.stock <= row.minStock
                                        ? 'font-semibold text-status-warning'
                                        : ''
                                "
                            >
                                {{ formatQuantity(row.stock) }}
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
                                        :data-test="`product-edit-${row.id}`"
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
                :icon="Package"
                title="مفيش أصناف مطابقة للبحث"
                description="جرّب تغيّر كلمة البحث أو تشيل الفلاتر."
                :secondary="{ label: 'امسح الفلاتر', href: '/products' }"
            />

            <EmptyState
                v-else
                large
                :icon="Package"
                title="لسه مافيش أصناف"
                description="الصنف هو البضاعة اللي بتشتريها وتبيعها — من غيره مش هتقدر تعمل فاتورة شراء ولا بيع."
            >
                <Button
                    v-if="manageMasterData"
                    data-test="product-create-empty"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    أضف أول صنف
                </Button>
            </EmptyState>

            <Pagination :meta="products" />
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل الصنف' : 'صنف جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        الاسم والكود وسعر البيع هم المطلوب — الباقي تقدر تضيفه
                        بعدين.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="product-name">اسم الصنف</Label>
                            <Input
                                id="product-name"
                                v-model="form.name"
                                placeholder="زيت عباد شمس 1 لتر"
                                autocomplete="off"
                                data-test="product-name"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-sku">كود الصنف</Label>
                            <Input
                                id="product-sku"
                                v-model="form.sku"
                                dir="ltr"
                                class="text-start"
                                autocomplete="off"
                                data-test="product-sku"
                            />
                            <InputError :message="form.errors.sku" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-unit">الوحدة</Label>
                            <select
                                id="product-unit"
                                v-model="form.unit"
                                :class="SELECT_CLASS"
                                data-test="product-unit"
                            >
                                <option v-for="u in units" :key="u" :value="u">
                                    {{ u }}
                                </option>
                            </select>
                            <InputError :message="form.errors.unit" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-category">
                                التصنيف
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <select
                                id="product-category"
                                v-model="form.category_id"
                                :class="SELECT_CLASS"
                                data-test="product-category"
                            >
                                <option value="">بدون تصنيف</option>
                                <option
                                    v-for="c in categories"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-sale-price">سعر البيع</Label>
                            <Input
                                id="product-sale-price"
                                v-model="form.sale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                dir="ltr"
                                class="text-start"
                                data-test="product-sale-price"
                            />
                            <InputError :message="form.errors.sale_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-wholesale-price">
                                سعر جملة الجملة
                                <span class="text-muted-foreground"
                                    >(اختياري)</span
                                >
                            </Label>
                            <Input
                                id="product-wholesale-price"
                                v-model="form.wholesale_price"
                                type="number"
                                step="0.01"
                                min="0"
                                dir="ltr"
                                class="text-start"
                                placeholder="سيبه فاضي عشان ياخد سعر البيع"
                                data-test="product-wholesale-price"
                            />
                            <InputError
                                :message="form.errors.wholesale_price"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-purchase-price">
                                تكلفة الشراء
                            </Label>
                            <Input
                                id="product-purchase-price"
                                v-model="form.purchase_price"
                                type="number"
                                step="0.01"
                                min="0"
                                dir="ltr"
                                class="text-start"
                                data-test="product-purchase-price"
                            />
                            <p class="text-xs text-muted-foreground">
                                فاتورة الشراء بتحدّثها بالتكلفة الواصلة.
                            </p>
                            <InputError :message="form.errors.purchase_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="product-min-stock">حد الطلب</Label>
                            <Input
                                id="product-min-stock"
                                v-model="form.min_stock"
                                type="number"
                                step="0.001"
                                min="0"
                                dir="ltr"
                                class="text-start"
                                data-test="product-min-stock"
                            />
                            <p class="text-xs text-muted-foreground">
                                الرصيد لما ينزل عنه بينوّر في تنبيهات المخزون.
                            </p>
                            <InputError :message="form.errors.min_stock" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="product-active"
                            v-model="form.is_active"
                            data-test="product-active"
                        />
                        <Label for="product-active" class="font-normal">
                            الصنف شغّال ويظهر في الفواتير
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="product-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة الصنف' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
