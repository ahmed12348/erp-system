<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

/**
 * /sales يفضل مفعّل وإحنا جوه /sales/123 كمان،
 * لكن /dashboard لازم تطابق تامة عشان ما تفضلش مفعّلة على طول.
 */
function isActive(item: NavItem): boolean {
    return item.href === '/dashboard'
        ? isCurrentUrl(item.href)
        : isCurrentOrParentUrl(item.href);
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
