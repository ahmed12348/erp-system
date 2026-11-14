<script setup lang="ts">
import { CircleCheck, CircleDashed, CircleX, Clock, Send } from '@lucide/vue';
import { computed } from 'vue';

/**
 * حالة الفاتورة في منظومة الفاتورة الإلكترونية.
 * الحالة دايماً أيقونة + نص، مش لون لوحده.
 */
const props = defineProps<{
    status: string | null;
}>();

const STATES = {
    valid: {
        label: 'مقبولة',
        icon: CircleCheck,
        classes: 'bg-status-good/10 text-status-good ring-status-good/25',
    },
    sent: {
        label: 'مُرسلة',
        icon: Send,
        classes: 'bg-chart-1/10 text-chart-1 ring-chart-1/25',
    },
    pending: {
        label: 'في انتظار الإرسال',
        icon: Clock,
        classes:
            'bg-status-warning/10 text-status-warning ring-status-warning/25',
    },
    submitted: {
        label: 'قيد المراجعة',
        icon: Clock,
        classes:
            'bg-status-warning/10 text-status-warning ring-status-warning/25',
    },
    rejected: {
        label: 'مرفوضة',
        icon: CircleX,
        classes:
            'bg-status-critical/10 text-status-critical ring-status-critical/25',
    },
} as const;

const state = computed(
    () =>
        STATES[props.status as keyof typeof STATES] ?? {
            label: 'لم تُرسل',
            icon: CircleDashed,
            classes: 'bg-muted text-muted-foreground ring-border',
        },
);
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset"
        :class="state.classes"
    >
        <component :is="state.icon" class="size-3.5" />
        {{ state.label }}
    </span>
</template>
