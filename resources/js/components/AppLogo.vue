<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground shadow-soft"
    >
        <AppLogoIcon class="size-4.5 fill-current" />
    </div>
    <div class="ms-1 grid flex-1 text-start text-sm">
        <span class="truncate leading-tight font-semibold">{{ name }}</span>
        <span class="truncate text-xs leading-tight text-sidebar-foreground/60">
            استيراد وتوزيع
        </span>
    </div>
</template>
