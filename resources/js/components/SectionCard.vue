<script setup lang="ts">
/** كارت قسم موحّد: عنوان + إجراءات + محتوى. */
defineProps<{
    title?: string;
    description?: string;
    /** شيل الحشو الداخلي لما المحتوى جدول أو قائمة بتاخد العرض كله */
    flush?: boolean;
}>();
</script>

<template>
    <section class="rounded-xl border border-border bg-card shadow-soft">
        <header
            v-if="title || $slots.actions"
            class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4"
        >
            <div class="min-w-0 space-y-0.5">
                <h2 v-if="title" class="truncate text-base font-semibold">
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </header>

        <div :class="flush ? '' : 'p-5'">
            <slot />
        </div>
    </section>
</template>
