<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatNumber } from '@/lib/format';

const page = usePage();

const count = computed(() => Number(page.props.lowStockCount ?? 0));

const label = computed(() =>
    count.value > 0
        ? `${formatNumber(count.value)} صنف تحت حد الطلب`
        : 'كل الأصناف فوق حد الطلب',
);
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <Button
                as-child
                variant="ghost"
                size="icon"
                class="relative size-9 text-muted-foreground hover:text-foreground"
                data-test="low-stock-bell"
            >
                <Link href="/inventory" :aria-label="label">
                    <Bell class="size-4.5" />
                    <span
                        v-if="count > 0"
                        class="absolute -end-0.5 -top-0.5 flex min-w-4.5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4.5 font-semibold text-destructive-foreground tabular-nums"
                    >
                        {{ count > 99 ? '99+' : formatNumber(count) }}
                    </span>
                </Link>
            </Button>
        </TooltipTrigger>
        <TooltipContent side="bottom">{{ label }}</TooltipContent>
    </Tooltip>
</template>
