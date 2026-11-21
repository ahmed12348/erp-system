<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeftRight, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

/**
 * تنبيه إن الفاتورة اتفتحت بالنوع الغلط.
 *
 * المستخدم بيبدأ المسح من شاشة المشتريات أو المبيعات، وساعات بيصوّر
 * فاتورة من النوع التاني. الاسم اللي على الورق هو اللي بيكشفها: تاجر
 * في فاتورة شراء، أو مورد في فاتورة بيع.
 *
 * التحويل مابيعيدش قراءة الصورة — القراءة الخام محفوظة، فبتتبني تاني
 * بمطابقة النوع الجديد.
 */

const props = defineProps<{
    draftId: number;
    /** النوع الحالي للمسودة */
    current: 'purchase' | 'sale';
    /** النوع اللي القراءة شكلها بتاعه */
    suggested: 'purchase' | 'sale';
    /** الاسم اللي اتقرا من الورق */
    partyName: string | null;
}>();

const message = computed(() =>
    props.suggested === 'sale'
        ? 'الاسم ده متسجّل عندك كتاجر مش مورد، والفاتورة مفتوحة كفاتورة شراء.'
        : 'الاسم ده متسجّل عندك كمورد مش تاجر، والفاتورة مفتوحة كفاتورة بيع.',
);

const action = computed(() =>
    props.suggested === 'sale' ? 'حوّلها لفاتورة بيع' : 'حوّلها لفاتورة شراء',
);

function convert() {
    router.post(`/drafts/${props.draftId}/switch-type`);
}
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-status-warning/50 bg-status-warning/10 px-4 py-3"
        data-test="scan-type-mismatch"
    >
        <div class="flex min-w-0 items-start gap-2">
            <TriangleAlert class="mt-0.5 size-4 shrink-0 text-status-warning" />
            <div class="min-w-0 text-sm">
                <p class="font-medium">{{ message }}</p>
                <p
                    v-if="partyName"
                    class="mt-0.5 text-xs text-muted-foreground"
                >
                    مكتوب على الورق: «{{ partyName }}»
                </p>
            </div>
        </div>

        <Button
            type="button"
            variant="outline"
            size="sm"
            data-test="scan-type-switch"
            @click="convert"
        >
            <ArrowLeftRight class="size-4" />
            {{ action }}
        </Button>
    </div>
</template>
