<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Boxes,
    ChartNoAxesCombined,
    History,
    LayoutDashboard,
    Package,
    ReceiptText,
    ShieldCheck,
    ShoppingCart,
    Tags,
    Truck,
    UserRound,
    Users,
    Wallet,
    Warehouse,
} from '@lucide/vue';
import { computed } from 'vue';
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
import { useAbilities } from '@/composables/useAbilities';
import { useActiveNav } from '@/composables/useActiveNav';
import type { NavGroup } from '@/types';

const { manageUsers } = useAbilities();

/**
 * القائمة الجانبية مقسّمة لمجموعتين: الحركة اليومية، ثم البيانات والتقارير.
 * التقسيم بيخلي القائمة أسهل في القراءة من ٨ عناصر ورا بعض.
 *
 * شاشة المستخدمين بتظهر للمدير بس — باقي الأدوار الرابط بيرجّعلهم 403
 * أصلاً، فعرضه في القائمة مالوش لازمة.
 */
const navGroups = computed<NavGroup[]>(() => [
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
            { title: 'المخازن', href: '/warehouses', icon: Warehouse },
            { title: 'الأصناف', href: '/products', icon: Boxes },
            { title: 'التصنيفات', href: '/categories', icon: Tags },
            { title: 'التجار', href: '/customers', icon: Users },
            { title: 'الموردون', href: '/suppliers', icon: Truck },
            { title: 'المندوبين', href: '/reps', icon: UserRound },
            { title: 'الخزينة', href: '/treasury', icon: Wallet },
            { title: 'التقارير', href: '/reports', icon: ChartNoAxesCombined },
            ...(manageUsers.value
                ? [
                      {
                          title: 'المستخدمين',
                          href: '/users',
                          icon: ShieldCheck,
                      },
                      {
                          title: 'سجل النشاط',
                          href: '/activity',
                          icon: History,
                      },
                  ]
                : []),
        ],
    },
]);

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
