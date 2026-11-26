<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bell,
    Boxes,
    Building2,
    ChartNoAxesCombined,
    ClipboardCheck,
    ClipboardList,
    Cog,
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
    Wallet2,
    Warehouse,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavSection from '@/components/NavSection.vue';
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
import { useSidebarSections } from '@/composables/useSidebarSections';
import type { NavItem, NavSection as Section } from '@/types';

const {
    manageUsers,
    manageMasterData,
    managePreparationOrders,
    writeStockDocuments,
} = useAbilities();

/** العنصر لو المستخدم ليه الصلاحية، وإلا مفيش */
function when(allowed: boolean, ...items: NavItem[]): NavItem[] {
    return allowed ? items : [];
}

/**
 * القائمة الجانبية: أقسام بتتفتح وتتقفل.
 *
 * وصلت لعشرين عنصر ورا بعض، وده بقى بيتقرا كقايمة مش كخريطة — المستخدم
 * بيدوّر بعينه بدل ما يعرف هو فين. التقسيم بيخلّي اللي ظاهر على طول
 * سبع سطور، والتفاصيل بتتفتح لما تحتاجها.
 *
 * الصلاحيات هنا **إخفاء شكلي بس** — المنع الحقيقي في الـ Gates على
 * السيرفر. لكن إخفاء اللي الدور مش هيقدر يفتحه بيوفّر على المستخدم
 * صفحة 403 ما كانش ليها لازمة أصلاً. والقسم اللي كل عناصره مخفية
 * بيختفي هو كمان — ترويسة فاضية أسوأ من مفيش ترويسة.
 */
const sections = computed<Section[]>(() =>
    [
        {
            key: 'dashboard',
            label: 'لوحة التحكم',
            icon: LayoutDashboard,
            href: '/dashboard',
        },
        {
            key: 'sales',
            label: 'المبيعات',
            icon: ReceiptText,
            items: [
                { title: 'فواتير البيع', href: '/sales', icon: ReceiptText },
                ...when(managePreparationOrders.value, {
                    title: 'طلبات التحضير',
                    href: '/preparation-orders',
                    icon: ClipboardList,
                }),
                {
                    title: 'مرتجعات المبيعات',
                    href: '/returns?type=sale_return',
                    icon: Undo2,
                    /*
                     * `/returns` من غير `type` بترجّع مرتجعات البيع من
                     * السيرفر، وصفحة المرتجع الواحد `/returns/12` مالهاش
                     * `type` أصلاً — فالعنصر ده هو صاحب المسار.
                     */
                    matchDefault: true,
                },
                { title: 'التجار', href: '/customers', icon: Users },
                { title: 'المناديب', href: '/reps', icon: UserRound },
            ],
        },
        {
            key: 'purchases',
            label: 'المشتريات',
            icon: ShoppingCart,
            items: [
                {
                    title: 'فواتير الشراء',
                    href: '/purchases',
                    icon: ShoppingCart,
                },
                {
                    title: 'مرتجعات المشتريات',
                    href: '/returns?type=purchase_return',
                    icon: Undo2,
                },
                { title: 'الموردين', href: '/suppliers', icon: Truck },
            ],
        },
        {
            key: 'inventory',
            label: 'المخزون',
            icon: Package,
            items: [
                { title: 'الأرصدة', href: '/inventory', icon: Package },
                { title: 'الأصناف', href: '/products', icon: Boxes },
                { title: 'التصنيفات', href: '/categories', icon: Tags },
                { title: 'المخازن', href: '/warehouses', icon: Warehouse },
                ...when(
                    writeStockDocuments.value,
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
                ),
            ],
        },
        {
            key: 'finance',
            label: 'المالية',
            icon: Wallet2,
            items: [
                { title: 'الخزينة', href: '/treasury', icon: Wallet },
                // شاشة المصروفات نفسها بتتطلب الصلاحية دي
                ...when(manageMasterData.value, {
                    title: 'المصروفات',
                    href: '/expenses',
                    icon: Receipt,
                }),
            ],
        },
        {
            key: 'reports',
            label: 'التقارير',
            icon: ChartNoAxesCombined,
            href: '/reports',
        },
        {
            key: 'settings',
            label: 'الإعدادات',
            icon: Cog,
            items: [
                ...when(
                    manageUsers.value,
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
                    {
                        title: 'بيانات الشركة',
                        href: '/settings/company',
                        icon: Building2,
                    },
                ),
                {
                    title: 'إعدادات التنبيهات',
                    href: '/settings/notifications',
                    icon: Bell,
                },
            ],
        },
    ].filter(
        (section) =>
            section.href !== undefined || (section.items ?? []).length > 0,
    ),
);

const { activeHref } = useActiveNav(sections);
const { isOpen, toggle } = useSidebarSections(sections, activeHref);
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

        <SidebarContent class="scrollbar-thin gap-1 px-2 py-0">
            <NavSection
                v-for="section in sections"
                :key="section.key"
                :section="section"
                :active-href="activeHref"
                :open="isOpen(section.key)"
                @update:open="toggle(section.key, $event)"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
