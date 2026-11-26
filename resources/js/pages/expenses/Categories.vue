<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatNumber } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Node = {
    id: number;
    name: string;
    code: string | null;
    isActive: boolean;
    expenseCount: number;
};

defineProps<{
    tree: (Node & { children: Node[] })[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/** إضافة بند تحت مجموعة، أو مجموعة جديدة لما `parent_id` يبقى فاضي */
const form = useForm({
    parent_id: '' as number | '',
    name: '',
    code: '',
});

const addingUnder = ref<number | null>(null);

function startAdd(parentId: number | null) {
    addingUnder.value = parentId;
    form.reset();
    form.parent_id = parentId ?? '';
}

function submit() {
    form.transform((data) => ({
        ...data,
        parent_id: data.parent_id === '' ? null : data.parent_id,
        code: data.code === '' ? null : data.code,
    })).post('/expenses/categories', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            addingUnder.value = null;
        },
    });
}

function toggleActive(node: Node) {
    router.put(
        `/expenses/categories/${node.id}`,
        { name: node.name, code: node.code, is_active: !node.isActive },
        { preserveScroll: true },
    );
}

function remove(node: Node) {
    if (!window.confirm(`هتمسح «${node.name}». تمام؟`)) {
        return;
    }

    router.delete(`/expenses/categories/${node.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="تصنيفات المصروفات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="تصنيفات المصروفات"
            description="مجموعة وتحتها بنود — الأكواد مظبوطة عشان تتربط بدليل حسابات كامل بعدين"
        >
            <template #actions>
                <Button
                    variant="outline"
                    data-test="add-group"
                    @click="startAdd(null)"
                >
                    <Plus class="size-4" />
                    مجموعة جديدة
                </Button>
            </template>
        </PageHeader>

        <!-- فورم الإضافة — بيظهر تحت المجموعة اللي اتدوس عليها -->
        <SectionCard
            v-if="addingUnder !== null || form.parent_id === ''"
            title="إضافة تصنيف"
        >
            <form class="grid gap-4 sm:grid-cols-4" @submit.prevent="submit">
                <div class="space-y-2 sm:col-span-2">
                    <Label for="name">الاسم</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        data-test="category-name"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="space-y-2">
                    <Label for="code">الكود</Label>
                    <Input
                        id="code"
                        v-model="form.code"
                        dir="ltr"
                        class="text-start"
                        placeholder="5150"
                        data-test="category-code"
                    />
                    <InputError :message="form.errors.code" />
                </div>
                <div class="flex items-end">
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="save-category"
                    >
                        <Spinner v-if="form.processing" class="size-4" />
                        إضافة
                    </Button>
                </div>
            </form>
        </SectionCard>

        <SectionCard
            v-for="group in tree"
            :key="group.id"
            :title="group.name"
            :description="group.code ? `كود ${group.code}` : undefined"
            flush
        >
            <template #actions>
                <Button
                    variant="outline"
                    size="sm"
                    :data-test="`add-child-${group.id}`"
                    @click="startAdd(group.id)"
                >
                    <Plus class="size-4" />
                    بند جديد
                </Button>
            </template>

            <ul class="divide-y divide-border">
                <li
                    v-for="child in group.children"
                    :key="child.id"
                    class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm"
                >
                    <span class="flex items-center gap-3">
                        <span
                            class="w-16 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ child.code ?? '—' }}
                        </span>
                        <span :class="{ 'opacity-50': !child.isActive }">
                            {{ child.name }}
                        </span>
                        <span
                            v-if="!child.isActive"
                            class="rounded-md bg-muted px-2 py-0.5 text-xs"
                        >
                            موقوف
                        </span>
                    </span>

                    <span class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground">
                            {{ formatNumber(child.expenseCount) }} مصروف
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            :data-test="`toggle-${child.id}`"
                            @click="toggleActive(child)"
                        >
                            {{ child.isActive ? 'إيقاف' : 'تفعيل' }}
                        </Button>
                        <!-- الحذف للفاضي بس — اللي عليه مصروفات بيتوقف -->
                        <Button
                            v-if="child.expenseCount === 0"
                            variant="ghost"
                            size="icon"
                            :data-test="`delete-${child.id}`"
                            @click="remove(child)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </span>
                </li>

                <li
                    v-if="!group.children.length"
                    class="px-5 py-6 text-center text-sm text-muted-foreground"
                >
                    مفيش بنود في المجموعة دي.
                </li>
            </ul>
        </SectionCard>
    </div>
</template>
