<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, TrendingDown, TrendingUp } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';

const props = defineProps<{
    label: string;
    value: string;
    href: string;
    icon: LucideIcon;
    /** نسبة التغيّر عن الفترة المقارنة — null يعني مفيش أساس للمقارنة */
    change?: number | null;
    changeNote?: string;
    /** نبرة الكارت: الافتراضي محايد، warning للأصناف الناقصة */
    tone?: 'default' | 'warning';
    hint?: string;
}>();

const isUp = computed(() => (props.change ?? 0) >= 0);

const toneClasses = computed(() =>
    props.tone === 'warning'
        ? 'bg-destructive/10 text-destructive'
        : 'bg-accent text-accent-foreground',
);
</script>

<template>
    <Link
        :href="href"
        class="group flex flex-col justify-between gap-4 rounded-xl border border-border bg-card p-5 shadow-soft transition-all hover:border-primary/35 hover:shadow-soft-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <div class="flex items-start justify-between gap-3">
            <span class="text-sm font-medium text-muted-foreground">
                {{ label }}
            </span>
            <span
                class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                :class="toneClasses"
            >
                <component :is="icon" class="size-4.5" />
            </span>
        </div>

        <div class="space-y-2">
            <p class="text-2xl leading-tight font-bold tracking-tight">
                {{ value }}
            </p>

            <div class="flex items-center gap-2 text-xs">
                <!-- الاتجاه مش بالّلون لوحده: فيه سهم + علامة -->
                <span
                    v-if="change !== null && change !== undefined"
                    class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 font-semibold"
                    :class="
                        isUp
                            ? 'bg-status-good/10 text-status-good'
                            : 'bg-status-critical/10 text-status-critical'
                    "
                >
                    <component
                        :is="isUp ? TrendingUp : TrendingDown"
                        class="size-3"
                    />
                    <span dir="ltr"
                        >{{ isUp ? '+' : '−'
                        }}{{ formatNumber(Math.abs(change), 1) }}%</span
                    >
                </span>
                <span v-if="changeNote" class="text-muted-foreground">
                    {{ changeNote }}
                </span>
                <span v-else-if="hint" class="text-muted-foreground">
                    {{ hint }}
                </span>
            </div>
        </div>

        <span
            class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors group-hover:text-primary"
        >
            عرض التفاصيل
            <ArrowLeft class="size-3.5" />
        </span>
    </Link>
</template>
