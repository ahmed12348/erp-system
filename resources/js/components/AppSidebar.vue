<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChartNoAxesCombined,
    LayoutDashboard,
    Package,
    ReceiptText,
    ShoppingCart,
    Truck,
    UserRound,
    Users,
    Wallet,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveNav } from '@/composables/useActiveNav';
import type { NavGroup } from '@/types';

/**
 * القائمة الجانبية مقسّمة لمجموعتين: الحركة اليومية، ثم البيانات والتقارير.
 * التقسيم بيخلي القائمة أسهل في القراءة من ٨ عناصر ورا بعض.
 */
const navGroups: NavGroup[] = [
    {
        label: 'الحركة اليومية',
        items: [
            { title: 'لوحة التحكم', href: '/dashboard', icon: LayoutDashboard },
            { title: 'المبيعات', href: '/sales', icon: ReceiptText },
            { title: 'المشتريات', href: '/purchases', icon: ShoppingCart },
            { title: 'المخزون', href: '/inventory', icon: Package },
        ],
    },
    {
        label: 'البيانات والتقارير',
        items: [
            { title: 'التجار', href: '/customers', icon: Users },
            { title: 'الموردون', href: '/suppliers', icon: Truck },
            { title: 'المندوبين', href: '/reps', icon: UserRound },
            { title: 'الخزينة', href: '/treasury', icon: Wallet },
            { title: 'التقارير', href: '/reports', icon: ChartNoAxesCombined },
        ],
    },
];

const { activeHref } = useActiveNav(navGroups);
</script>

<template>
    <!-- side="right" هو اللي بيحط القائمة على يمين الشاشة في الواجهة العربية -->
    <Sidebar collapsible="icon" side="right" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="scrollbar-thin">
            <NavMain
                v-for="group in navGroups"
                :key="group.label"
                :label="group.label"
                :items="group.items"
                :active-href="activeHref"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
