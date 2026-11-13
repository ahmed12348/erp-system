<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
    label?: string;
    /** المسار المفعّل الوحيد في القائمة كلها — محسوب في AppSidebar */
    activeHref?: string | null;
}>();

function isActive(item: NavItem): boolean {
    return (
        props.activeHref != null &&
        (toUrl(item.href) ?? '').replace(/\/+$/, '') === props.activeHref
    );
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item)"
                    :tooltip="item.title"
                    class="font-medium transition-colors data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground data-[active=true]:shadow-soft"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <!-- شريط رفيع على حافة العنصر المفعّل -->
                        <span
                            v-if="isActive(item)"
                            aria-hidden="true"
                            class="absolute inset-y-1.5 end-0 w-1 rounded-full bg-sidebar-primary group-data-[collapsible=icon]:hidden"
                        />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
