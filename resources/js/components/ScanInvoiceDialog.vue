<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FileText, ScanLine, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { AiError, aiRequest } from '@/lib/ai';

/**
 * «مسح فاتورة» — بيرفع صورة أو PDF وبيودّيك على المسودة.
 *
 * الكمبوننت ده مابيعرفش حاجة عن شكل الفاتورة: بيرفع الملف، والسيرفر
 * بيقراه ويعمل مسودة ويرجّع لينكها، وإحنا بنروح هناك. عشان كده هو
 * نفسه بيشتغل على صفحة البيع وصفحة الشراء وصفحات القوايم.
 */
type ScanResponse = {
    draftId: number;
    redirect: string;
};

const props = withDefaults(
    defineProps<{
        type: 'purchase' | 'sale';
        /** على صفحات القوايم الزرار بيبقى جنب «فاتورة جديدة» */
        variant?: 'outline' | 'ghost';
    }>(),
    { variant: 'outline' },
);

/** نفس حدود المرفقات في النظام */
const MAX_BYTES = 8 * 1024 * 1024;
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

const open = ref(false);
const scanning = ref(false);
const error = ref<string | null>(null);
const selected = ref<File | null>(null);
const input = ref<HTMLInputElement | null>(null);

const label = computed(() =>
    props.type === 'purchase' ? 'فاتورة الشراء' : 'فاتورة البيع',
);

function openDialog() {
    selected.value = null;
    error.value = null;
    open.value = true;
}

function pick(event: Event) {
    const files = (event.target as HTMLInputElement).files;
    const file = files?.[0] ?? null;

    error.value = null;

    if (!file) {
        selected.value = null;

        return;
    }

    // بنتأكد هنا قبل ما نرفع — أرحم للمستخدم من إنه يستنى الرفع ويترفض
    if (!ACCEPTED.includes(file.type)) {
        error.value = 'الملف لازم يكون صورة (JPG أو PNG أو WEBP) أو PDF.';
        selected.value = null;

        return;
    }

    if (file.size > MAX_BYTES) {
        error.value = 'الملف لازم يكون أقل من 8 ميجا.';
        selected.value = null;

        return;
    }

    selected.value = file;
}

async function scan() {
    const file = selected.value;

    if (!file || scanning.value) {
        return;
    }

    scanning.value = true;
    error.value = null;

    const body = new FormData();

    body.append('file', file);
    body.append('type', props.type);

    try {
        /*
         * المسح بيعمل مسودة على السيرفر وبيرجّع لينكها. المسودة
         * ما بتلمسش مخزون ولا أرصدة — هي بس اللي اتقرا من الصورة
         * مستنية مراجعة.
         */
        const result = await aiRequest<ScanResponse>('/ai/scan-invoice', {
            method: 'POST',
            body,
        });

        open.value = false;

        router.visit(result.redirect);
    } catch (e) {
        error.value =
            e instanceof AiError
                ? e.message
                : 'مقدرتش أقرا الفاتورة. جرّب تاني.';
    } finally {
        scanning.value = false;
    }
}
</script>

<template>
    <Button
        type="button"
        :variant="variant"
        size="sm"
        data-test="scan-invoice-button"
        @click="openDialog"
    >
        <ScanLine class="size-4" />
        مسح فاتورة
    </Button>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md" data-test="scan-invoice-dialog">
            <DialogHeader class="text-start">
                <DialogTitle>مسح {{ label }}</DialogTitle>
                <DialogDescription>
                    ارفع صورة أو PDF. هتتعمل **مسودة** تقدر تراجعها قبل ما
                    تتأكّد — المخزون والأرصدة ما بيتحركوش غير بعد التأكيد.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <button
                    type="button"
                    class="flex w-full flex-col items-center gap-2 rounded-lg border border-dashed border-input px-4 py-7 text-sm text-muted-foreground transition-colors hover:border-primary/50 hover:bg-accent"
                    data-test="scan-invoice-picker"
                    @click="input?.click()"
                >
                    <component
                        :is="selected ? FileText : Upload"
                        class="size-6"
                    />
                    <span v-if="selected" class="font-medium text-foreground">
                        {{ selected.name }}
                    </span>
                    <span v-else>اختار صورة الفاتورة أو ملف PDF</span>
                    <span class="text-xs">
                        JPG أو PNG أو WEBP أو PDF — أقل من 8 ميجا
                    </span>
                </button>

                <input
                    ref="input"
                    type="file"
                    class="hidden"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    data-test="scan-invoice-input"
                    @change="pick"
                />

                <p
                    v-if="error"
                    class="rounded-lg bg-destructive/10 px-3 py-2 text-xs text-destructive"
                    data-test="scan-invoice-error"
                >
                    {{ error }}
                </p>

                <p
                    v-if="scanning"
                    class="text-center text-xs text-muted-foreground"
                >
                    بقرا الفاتورة… ممكن تاخد شوية حسب وضوح الصورة.
                </p>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="scanning"
                    @click="open = false"
                >
                    إلغاء
                </Button>
                <Button
                    type="button"
                    :disabled="!selected || scanning"
                    data-test="scan-invoice-submit"
                    @click="scan"
                >
                    <Spinner v-if="scanning" class="size-4" />
                    <ScanLine v-else class="size-4" />
                    اقرا الفاتورة
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
