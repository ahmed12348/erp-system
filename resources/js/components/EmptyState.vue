<script setup lang="ts">
import { Inbox } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    title: string;
    description?: string;
    icon?: LucideIcon;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center"
    >
        <span
            class="flex size-11 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <component :is="icon ?? Inbox" class="size-5" />
        </span>
        <p class="text-sm font-medium">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-xs text-muted-foreground">
            {{ description }}
        </p>
        <slot />
    </div>
</template>
