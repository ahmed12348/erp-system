<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    profile: {
        id: number;
        name: string;
        logo: string | null;
        address: string | null;
        phones: string | null;
        taxNumber: string | null;
        commercialRegistry: string | null;
        footerNote: string | null;
    };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const form = useForm({
    name: props.profile.name,
    address: props.profile.address ?? '',
    phones: props.profile.phones ?? '',
    tax_number: props.profile.taxNumber ?? '',
    commercial_registry: props.profile.commercialRegistry ?? '',
    footer_note: props.profile.footerNote ?? '',
    logo: null as File | null,
    remove_logo: false as boolean,
});

/** معاينة اللوجو الجديد قبل الحفظ */
const preview = ref<string | null>(props.profile.logo);

function onLogoPicked(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    form.logo = file;
    form.remove_logo = false;
    preview.value = file ? URL.createObjectURL(file) : props.profile.logo;
}

function removeLogo() {
    form.logo = null;
    form.remove_logo = true;
    preview.value = null;
}

function submit() {
    // الرفع محتاج POST بـ multipart، فالتعديل بيعدّي على method spoofing
    form.post('/settings/company', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.logo = null;
            form.remove_logo = false;
        },
    });
}
</script>

<template>
    <Head title="بيانات الشركة" />

    <form
        class="flex flex-1 flex-col gap-5 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <PageHeader
            title="بيانات الشركة"
            description="بتظهر على كل فاتورة ومرتجع وكشف حساب وطلب تحضير وتقرير — مطبوع وPDF"
        />

        <div class="grid gap-5 lg:grid-cols-3">
            <SectionCard title="الأساسي" class="lg:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="name">اسم الشركة</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            data-test="company-name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="address">العنوان</Label>
                        <Input
                            id="address"
                            v-model="form.address"
                            data-test="company-address"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phones">التليفونات</Label>
                        <Input
                            id="phones"
                            v-model="form.phones"
                            dir="ltr"
                            class="text-start"
                            placeholder="01000000000 / 0223456789"
                            data-test="company-phones"
                        />
                        <InputError :message="form.errors.phones" />
                    </div>

                    <div class="space-y-2">
                        <Label for="tax_number">الرقم الضريبي</Label>
                        <Input
                            id="tax_number"
                            v-model="form.tax_number"
                            dir="ltr"
                            class="text-start"
                            data-test="company-tax"
                        />
                        <InputError :message="form.errors.tax_number" />
                    </div>

                    <div class="space-y-2">
                        <Label for="commercial_registry">السجل التجاري</Label>
                        <Input
                            id="commercial_registry"
                            v-model="form.commercial_registry"
                            dir="ltr"
                            class="text-start"
                            data-test="company-registry"
                        />
                        <InputError
                            :message="form.errors.commercial_registry"
                        />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="footer_note">ملاحظة أسفل المستند</Label>
                        <textarea
                            id="footer_note"
                            v-model="form.footer_note"
                            rows="2"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            placeholder="شروط السداد، أو أي جملة بتتكرر على كل ورقة"
                            data-test="company-footer"
                        ></textarea>
                        <InputError :message="form.errors.footer_note" />
                    </div>
                </div>
            </SectionCard>

            <SectionCard title="اللوجو">
                <div class="space-y-4">
                    <div
                        class="flex h-32 items-center justify-center rounded-lg border border-dashed border-border bg-muted/30"
                    >
                        <img
                            v-if="preview"
                            :src="preview"
                            alt="لوجو الشركة"
                            class="max-h-28 max-w-full object-contain"
                            data-test="logo-preview"
                        />
                        <Building2
                            v-else
                            class="size-10 text-muted-foreground"
                        />
                    </div>

                    <input
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm"
                        data-test="company-logo-input"
                        @change="onLogoPicked"
                    />
                    <InputError :message="form.errors.logo" />

                    <Button
                        v-if="preview"
                        type="button"
                        variant="ghost"
                        size="sm"
                        data-test="remove-logo"
                        @click="removeLogo"
                    >
                        <Trash2 class="size-4" />
                        شيل اللوجو
                    </Button>

                    <p class="text-xs text-muted-foreground">
                        PNG أو JPG أو WEBP، أقل من 2 ميجا. بيظهر في ترويسة كل
                        مستند مطبوع وفي الـ PDF.
                    </p>
                </div>
            </SectionCard>
        </div>

        <div class="flex justify-end">
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="save-company"
            >
                <Spinner v-if="form.processing" class="size-4" />
                حفظ
            </Button>
        </div>
    </form>
</template>
