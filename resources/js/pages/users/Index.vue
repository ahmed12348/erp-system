<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, ShieldCheck, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
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
import { formatDate } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

type Row = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    roleLabel: string;
    createdAt: string | null;
    twoFactorEnabled: boolean;
};

type Role = {
    value: string;
    label: string;
    description: string;
};

const props = defineProps<{
    users: Row[];
    roles: Role[];
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

/** null = بنضيف مستخدم جديد، وغير كده بنعدّل المستخدم ده */
const editing = ref<Row | null>(null);
const open = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'sales',
});

/** شرح الدور المختار — بيظهر تحت القايمة عشان المدير يعرف هو بيدي إيه */
const selectedRole = computed(
    () => props.roles.find((role) => role.value === form.role) ?? null,
);

function openCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'sales',
    });
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(row: Row) {
    editing.value = row;
    form.defaults({
        name: row.name,
        email: row.email,
        // كلمة المرور بتفضل فاضية في التعديل — فاضية معناها «سيبها زي ما هي»
        password: '',
        password_confirmation: '',
        role: row.role ?? 'sales',
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
        form.put(`/users/${editing.value.id}`, options);
    } else {
        form.post('/users', options);
    }
}

function remove(row: Row) {
    if (!window.confirm(`متأكد إنك عايز تحذف المستخدم «${row.name}»؟`)) {
        return;
    }

    router.delete(`/users/${row.id}`, { preserveScroll: true });
}

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="المستخدمين" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="المستخدمين والأدوار"
            description="مين يدخل على النظام وبأي صلاحية — الشاشة دي للمدير لوحده"
        >
            <template #actions>
                <Button data-test="user-create" @click="openCreate">
                    <Plus class="size-4" />
                    مستخدم جديد
                </Button>
            </template>
        </PageHeader>

        <SectionCard title="كل المستخدمين" flush>
            <div v-if="users.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-2.5 text-start font-medium">
                                المستخدم
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الإيميل
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                الدور
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                التحقق بخطوتين
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                مسجّل من
                            </th>
                            <th class="px-5 py-2.5 text-start font-medium">
                                <span class="sr-only">إجراءات</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in users"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                        >
                            <td class="px-5 py-3 font-medium">
                                {{ row.name }}
                                <span
                                    v-if="row.id === currentUserId"
                                    class="ms-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    انت
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                <span dir="ltr">{{ row.email }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        row.role === 'admin'
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ row.roleLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    v-if="row.twoFactorEnabled"
                                    class="text-status-good"
                                >
                                    مفعّل
                                </span>
                                <span v-else class="text-muted-foreground">
                                    مش مفعّل
                                </span>
                            </td>
                            <td class="px-5 py-3 text-muted-foreground">
                                {{ formatDate(row.createdAt) }}
                            </td>
                            <td class="px-5 py-3">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        :aria-label="`تعديل ${row.name}`"
                                        :data-test="`user-edit-${row.id}`"
                                        @click="openEdit(row)"
                                    >
                                        <Pencil class="size-4" />
                                    </button>
                                    <button
                                        v-if="row.id !== currentUserId"
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
                :icon="ShieldCheck"
                title="لسه مافيش مستخدمين"
                description="أضف حسابات للمحاسب والمندوبين وحدّد دور كل واحد فيهم."
            >
                <Button data-test="user-create-empty" @click="openCreate">
                    <Plus class="size-4" />
                    أضف أول مستخدم
                </Button>
            </EmptyState>
        </SectionCard>

        <SectionCard title="الأدوار وصلاحياتها">
            <dl class="space-y-3 text-sm">
                <div v-for="role in roles" :key="role.value">
                    <dt class="font-medium">{{ role.label }}</dt>
                    <dd class="text-muted-foreground">
                        {{ role.description }}
                    </dd>
                </div>
            </dl>
        </SectionCard>

        <Dialog v-model:open="open">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editing ? 'تعديل المستخدم' : 'مستخدم جديد' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editing
                                ? 'سيب خانة كلمة المرور فاضية لو مش عايز تغيّرها.'
                                : 'المستخدم الجديد بيقدر يدخل على طول بالإيميل وكلمة المرور دي.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="user-name">الاسم</Label>
                        <Input
                            id="user-name"
                            v-model="form.name"
                            placeholder="منى عبد الرحمن"
                            autocomplete="off"
                            data-test="user-name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="user-email">الإيميل</Label>
                        <Input
                            id="user-email"
                            v-model="form.email"
                            type="email"
                            dir="ltr"
                            class="text-start"
                            autocomplete="off"
                            data-test="user-email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="user-role">الدور</Label>
                        <select
                            id="user-role"
                            v-model="form.role"
                            :class="SELECT_CLASS"
                            data-test="user-role"
                        >
                            <option
                                v-for="role in roles"
                                :key="role.value"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <p
                            v-if="selectedRole"
                            class="text-xs text-muted-foreground"
                        >
                            {{ selectedRole.description }}
                        </p>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="user-password">
                                كلمة المرور
                                <span
                                    v-if="editing"
                                    class="text-muted-foreground"
                                >
                                    (اختياري)
                                </span>
                            </Label>
                            <Input
                                id="user-password"
                                v-model="form.password"
                                type="password"
                                dir="ltr"
                                class="text-start"
                                autocomplete="new-password"
                                data-test="user-password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="user-password-confirm">
                                تأكيد كلمة المرور
                            </Label>
                            <Input
                                id="user-password-confirm"
                                v-model="form.password_confirmation"
                                type="password"
                                dir="ltr"
                                class="text-start"
                                autocomplete="new-password"
                                data-test="user-password-confirm"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            data-test="user-submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'حفظ التعديل' : 'إضافة المستخدم' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
