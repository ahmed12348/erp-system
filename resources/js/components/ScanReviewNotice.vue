<script setup lang="ts">
import { FileText, X } from '@lucide/vue';
import { computed } from 'vue';
import type { ScanReview } from '@/composables/useInvoiceScan';

/**
 * شريط بيظهر بعد المسح: الملف اللي اتقرا، وكام خانة لسه محتاجة مراجعة.
 *
 * الهدف إن المستخدم يعرف إن اللي قدامه مقروء من صورة مش مكتوب بإيده،
 * وإن الأصفر معناه «بص على دي» مش «في غلطة».
 */

const props = defineProps<{
    review: ScanReview;
    fileName: string;
    pendingCount: number;
}>();

const emit = defineEmits<{ remove: [] }>();

const partyMissing = computed(() => props.review.party.confidence === 'none');

const unmatchedItems = computed(
    () =>
        props.review.items.filter((item) => item.confidence === 'none').length,
);
</script>

<template>
    <div
        class="rounded-lg border border-yellow-400/60 bg-yellow-50 px-4 py-3 dark:border-yellow-500/40 dark:bg-yellow-500/10"
        data-test="scan-review-notice"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <FileText
                    class="size-4 shrink-0 text-yellow-700 dark:text-yellow-500"
                />
                <p class="min-w-0 text-sm">
                    <span class="font-medium">اتقرت من:</span>
                    <span class="text-muted-foreground">{{ fileName }}</span>
                </p>
            </div>

            <button
                type="button"
                class="flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                data-test="scan-remove"
                @click="emit('remove')"
            >
                <X class="size-3.5" />
                شيل المرفق
            </button>
        </div>

        <p class="mt-2 text-xs text-muted-foreground">
            <template v-if="pendingCount > 0">
                فيه
                <span class="font-medium text-foreground">{{
                    pendingCount
                }}</span>
                خانة ملوّنة بالأصفر محتاجة تراجعها قبل الحفظ<template
                    v-if="partyMissing || unmatchedItems"
                >
                    —
                    <template v-if="partyMissing">
                        الاسم المكتوب على الفاتورة «{{
                            review.party.extracted
                        }}» مش موجود عندك
                    </template>
                    <template v-if="partyMissing && unmatchedItems"
                        >، و</template
                    >
                    <template v-if="unmatchedItems">
                        {{ unmatchedItems }} صنف محتاج تختاره أو تضيفه
                    </template> </template
                >.
            </template>
            <template v-else>
                كل الخانات اتطابقت. راجعها بسرعة واضغط حفظ.
            </template>
            الملف هيتعلّق على الفاتورة بعد الحفظ.
        </p>
    </div>
</template>
