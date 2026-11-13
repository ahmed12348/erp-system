<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ConfigProvider } from 'reka-ui';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <!--
      ConfigProvider بيبلّغ مكوّنات reka-ui كلها إن الاتجاه RTL، فالقوائم
      المنسدلة والتولتيب والسلكت بتتموضع وتتنقّل بالكيبورد في الاتجاه الصح.
    -->
    <ConfigProvider dir="rtl">
        <div
            v-if="variant === 'header'"
            class="flex min-h-screen w-full flex-col"
        >
            <slot />
        </div>
        <SidebarProvider v-else :default-open="isOpen">
            <slot />
        </SidebarProvider>
    </ConfigProvider>
</template>
