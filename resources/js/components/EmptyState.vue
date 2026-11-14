<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Inbox, Plus } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';

export type EmptyAction = {
    label: string;
    href: string;
    icon?: LucideIcon;
};

defineProps<{
    title: string;
    description?: string;
    icon?: LucideIcon;
    /** زرار الإجراء الأساسي — «فاتورة بيع جديدة» مثلاً */
    action?: EmptyAction | null;
    /** إجراء ثانوي، زي «امسح الفلاتر» */
    secondary?: EmptyAction | null;
    /** حالة فاضية كبيرة لما تكون هي كل محتوى الصفحة */
    large?: boolean;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 px-6 text-center"
        :class="large ? 'py-16' : 'py-12'"
    >
        <span
            class="flex items-center justify-center rounded-full bg-muted text-muted-foreground"
            :class="large ? 'size-14' : 'size-11'"
        >
            <component
                :is="icon ?? Inbox"
                :class="large ? 'size-6' : 'size-5'"
            />
        </span>

        <p class="font-medium" :class="large ? 'text-base' : 'text-sm'">
            {{ title }}
        </p>

        <p
            v-if="description"
            class="max-w-sm text-muted-foreground"
            :class="large ? 'text-sm' : 'text-xs'"
        >
            {{ description }}
        </p>

        <div
            v-if="action || secondary || $slots.default"
            class="mt-3 flex flex-wrap items-center justify-center gap-2"
        >
            <Button v-if="action" as-child :size="large ? 'default' : 'sm'">
                <Link :href="action.href">
                    <component :is="action.icon ?? Plus" class="size-4" />
                    {{ action.label }}
                </Link>
            </Button>

            <Button
                v-if="secondary"
                as-child
                variant="outline"
                :size="large ? 'default' : 'sm'"
            >
                <Link :href="secondary.href">
                    <component
                        v-if="secondary.icon"
                        :is="secondary.icon"
                        class="size-4"
                    />
                    {{ secondary.label }}
                </Link>
            </Button>

            <slot />
        </div>
    </div>
</template>
