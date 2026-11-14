<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Download,
    FileText,
    Image as ImageIcon,
    Trash2,
    Upload,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatNumber } from '@/lib/format';

export type AttachmentItem = {
    id: number;
    name: string;
    mimeType: string;
    size: number;
    isImage: boolean;
    isPdf: boolean;
    uploadedBy: string | null;
    uploadedAt: string | null;
    previewUrl: string;
    downloadUrl: string;
    notes: string | null;
};

const props = withDefaults(
    defineProps<{
        attachments: AttachmentItem[];
        /** مسار الرفع: /invoices/{id}/attachments أو /payments/{id}/attachments */
        uploadUrl: string;
        /** خلّيهم null جوه حوار ليه عنوان بالفعل، عشان ما نكرّرش العنوان */
        heading?: string | null;
        subheading?: string | null;
    }>(),
    {
        heading: 'المرفقات',
        subheading:
            'صور أو ملفات PDF — فاتورة المورد، إيصال التحويل، بوليصة الشحن',
    },
);

const fileInput = ref<HTMLInputElement | null>(null);
const preview = ref<AttachmentItem | null>(null);

const form = useForm<{ file: File | null; notes: string }>({
    file: null,
    notes: '',
});

function pick() {
    fileInput.value?.click();
}

function onFile(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        return;
    }

    form.file = file;
    form.post(props.uploadUrl, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.reset();
            target.value = '';
        },
    });
}

function remove(attachment: AttachmentItem) {
    if (!window.confirm(`متأكد إنك عايز تحذف «${attachment.name}»؟`)) {
        return;
    }

    router.delete(`/attachments/${attachment.id}`, { preserveScroll: true });
}

/** بايت → كيلو/ميجا بأرقام لاتينية */
function humanSize(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${formatNumber(bytes / (1024 * 1024), 1)} ميجا`;
    }

    return `${formatNumber(bytes / 1024)} كيلو`;
}
</script>

<template>
    <SectionCard
        :title="heading ?? undefined"
        :description="subheading ?? undefined"
    >
        <template #actions>
            <Button
                variant="outline"
                size="sm"
                :disabled="form.processing"
                data-test="attach-button"
                @click="pick"
            >
                <Spinner v-if="form.processing" />
                <Upload v-else class="size-4" />
                رفع مرفق
            </Button>
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                data-test="attach-input"
                @change="onFile"
            />
        </template>

        <InputError class="mb-3" :message="form.errors.file" />

        <!-- عمودين لما مساحة الكارت تسمح، مش حسب عرض الشاشة — عشان يشتغل جوه الحوار كمان -->
        <div v-if="attachments.length" class="@container/list">
            <ul class="grid gap-3 @md/list:grid-cols-2">
                <li
                    v-for="attachment in attachments"
                    :key="attachment.id"
                    class="flex items-center gap-3 rounded-lg border border-border p-3"
                >
                    <!-- مصغّرة للصور، أيقونة للـ PDF -->
                    <button
                        type="button"
                        class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted text-muted-foreground"
                        :aria-label="`معاينة ${attachment.name}`"
                        @click="preview = attachment"
                    >
                        <img
                            v-if="attachment.isImage"
                            :src="attachment.previewUrl"
                            :alt="attachment.name"
                            class="size-full object-cover"
                        />
                        <FileText v-else class="size-5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ attachment.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ humanSize(attachment.size) }}
                            <template v-if="attachment.uploadedAt">
                                · {{ formatDate(attachment.uploadedAt) }}
                            </template>
                            <template v-if="attachment.uploadedBy">
                                · {{ attachment.uploadedBy }}
                            </template>
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <a
                            :href="attachment.downloadUrl"
                            class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            :aria-label="`تحميل ${attachment.name}`"
                        >
                            <Download class="size-4" />
                        </a>
                        <button
                            type="button"
                            class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                            :aria-label="`حذف ${attachment.name}`"
                            @click="remove(attachment)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            مفيش مرفقات. ارفع صورة أو PDF للفاتورة أو إيصال التحويل.
        </p>

        <!-- المعاينة -->
        <Dialog :open="preview !== null" @update:open="preview = null">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle class="truncate">{{
                        preview?.name
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ preview ? humanSize(preview.size) : '' }}
                        <template v-if="preview?.notes">
                            · {{ preview.notes }}</template
                        >
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-[70vh] overflow-auto rounded-lg bg-muted">
                    <img
                        v-if="preview?.isImage"
                        :src="preview.previewUrl"
                        :alt="preview.name"
                        class="mx-auto block"
                    />
                    <iframe
                        v-else-if="preview?.isPdf"
                        :src="preview.previewUrl"
                        class="h-[70vh] w-full"
                        :title="preview.name"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 p-10 text-center text-sm text-muted-foreground"
                    >
                        <ImageIcon class="size-6" />
                        مش قادرين نعرض الملف ده — حمّله عشان تفتحه.
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button v-if="preview" as-child variant="outline">
                        <a :href="preview.downloadUrl">
                            <Download class="size-4" />
                            تحميل
                        </a>
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </SectionCard>
</template>
