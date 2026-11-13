<script setup lang="ts">
/**
 * عنوان الصفحة الموحّد: عنوان + وصف مختصر + مكان لأزرار الإجراءات.
 */
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="min-w-0 space-y-1">
            <h1 class="truncate text-2xl font-bold tracking-tight">
                {{ title }}
            </h1>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
