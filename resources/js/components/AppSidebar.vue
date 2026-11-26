<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Boxes,
    Building2,
    ChartNoAxesCombined,
    ClipboardCheck,
    ClipboardList,
    History,
    LayoutDashboard,
    Package,
    Receipt,
    ReceiptText,
    ScanBarcode,
    ShieldCheck,
    ShoppingCart,
    Tags,
    Truck,
    Undo2,
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

const { manageUsers, managePreparationOrders, writeStockDocuments } =
    useAbilities();

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
            ...(managePreparationOrders.value
                ? [
                      {
                          title: 'طلبات التحضير',
                          href: '/preparation-orders',
                          icon: ClipboardList,
                      },
                  ]
                : []),
            { title: 'المرتجعات', href: '/returns', icon: Undo2 },
            { title: 'المخزون', href: '/inventory', icon: Package },
            ...(writeStockDocuments.value
                ? [
                      {
                          title: 'أذون المخزن',
                          href: '/stock-adjustments',
                          icon: ClipboardCheck,
                      },
                      {
                          title: 'الجرد',
                          href: '/stock-counts',
                          icon: ScanBarcode,
                      },
                  ]
                : []),
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
            { title: 'المصروفات', href: '/expenses', icon: Receipt },
            { title: 'الخزينة', href: '/treasury', icon: Wallet },
            { title: 'التقارير', href: '/reports', icon: ChartNoAxesCombined },
            ...(manageUsers.value
                ? [
                      {
                          title: 'بيانات الشركة',
                          href: '/settings/company',
                          icon: Building2,
                      },
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
