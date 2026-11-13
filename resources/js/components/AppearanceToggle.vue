<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const label = computed(() =>
    isDark.value ? 'التبديل للوضع الفاتح' : 'التبديل للوضع الداكن',
);

function toggle() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="size-9 text-muted-foreground hover:text-foreground"
                :aria-label="label"
                data-test="appearance-toggle"
                @click="toggle"
            >
                <Moon v-if="isDark" class="size-4.5" />
                <Sun v-else class="size-4.5" />
            </Button>
        </TooltipTrigger>
        <TooltipContent side="bottom">{{ label }}</TooltipContent>
    </Tooltip>
</template>
