<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import type { NavItem, NavSection } from '@/types';

const props = defineProps<{
    section: NavSection;
    /** المسار المفعّل الوحيد في القائمة كلها — محسوب في AppSidebar */
    activeHref?: string | null;
    open: boolean;
}>();

const emit = defineEmits<{ 'update:open': [boolean] }>();

const { state, isMobile } = useSidebar();

/**
 * القائمة المصغّرة (أيقونات بس) مالهاش مكان تفتح فيه القسم، فالأقسام
 * بتبقى قوايم منبثقة بدل ما تتفتح لتحت. على الموبايل القائمة بتبقى
 * مفتوحة بعرضها الكامل جوه الـ sheet، فبتفضل بتتفتح وتتقفل عادي.
 */
const asFlyout = computed(() => state.value === 'collapsed' && !isMobile.value);

function isActive(item: NavItem): boolean {
    return props.activeHref != null && item.href === props.activeHref;
}

/** القسم اللي فيه الصفحة الحالية — ترويسته بتاخد لون خفيف */
const hasActive = computed(() =>
    (props.section.items ?? []).some((item) => isActive(item)),
);

/*
 * القائمة المنبثقة بتفتح بالمرور (hover) زي ما المستخدم متوقّع من
 * قائمة أيقونات، وبتفضل كمان تفتح بالضغط ولوحة المفاتيح لأنها
 * `DropdownMenu` حقيقية — المرور لوحده مابيشتغلش بالكيبورد.
 */
const flyoutOpen = ref(false);
let closeTimer: ReturnType<typeof setTimeout> | undefined;

function hover(open: boolean) {
    clearTimeout(closeTimer);

    if (open) {
        flyoutOpen.value = true;

        return;
    }

    // مهلة صغيرة عشان الماوس يعدّي من الأيقونة للقائمة من غير ما تتقفل
    closeTimer = setTimeout(() => {
        flyoutOpen.value = false;
    }, 120);
}
</script>

<template>
    <!-- قسم برابط واحد (لوحة التحكم، التقارير) — مفيش حاجة تتفتح -->
    <SidebarMenu v-if="section.href">
        <SidebarMenuItem>
            <SidebarMenuButton
                as-child
                :is-active="activeHref === section.href"
                :tooltip="section.label"
                class="font-medium transition-colors data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground data-[active=true]:shadow-soft"
            >
                <Link :href="section.href">
                    <component :is="section.icon" />
                    <span>{{ section.label }}</span>
                    <span
                        v-if="activeHref === section.href"
                        aria-hidden="true"
                        class="absolute inset-y-1.5 end-0 w-1 rounded-full bg-sidebar-primary group-data-[collapsible=icon]:hidden"
                    />
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>

    <!-- قسم مصغّر: قائمة منبثقة على الجنب -->
    <SidebarMenu v-else-if="asFlyout">
        <SidebarMenuItem @mouseenter="hover(true)" @mouseleave="hover(false)">
            <!--
                `modal` مقفول: القائمة بتتفتح بالمرور، والوضع الـ modal
                بيقفل التفاعل مع باقي الصفحة — يعني مجرد ما الماوس يعدّي
                على أيقونة، باقي الشاشة بتتجمّد.
            -->
            <DropdownMenu
                :open="flyoutOpen"
                :modal="false"
                @update:open="flyoutOpen = $event"
            >
                <DropdownMenuTrigger as-child>
                    <!--
                        من غير `tooltip` هنا: القائمة المنبثقة نفسها هي
                        اللي بتقول اسم القسم، وتلميح فوقها بيتخانق معاها
                        على نفس الزرار.
                    -->
                    <SidebarMenuButton
                        :is-active="hasActive"
                        :aria-label="section.label"
                        class="font-medium transition-colors data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground"
                    >
                        <component :is="section.icon" />
                        <span>{{ section.label }}</span>
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <!-- side="left" لأن القائمة على يمين الشاشة في الواجهة العربية -->
                <DropdownMenuContent
                    side="left"
                    align="start"
                    :side-offset="8"
                    class="min-w-48 p-1"
                    :data-test="`flyout-${section.key}`"
                    @mouseenter="hover(true)"
                    @mouseleave="hover(false)"
                >
                    <p
                        class="px-2 py-1.5 text-xs font-medium text-muted-foreground"
                    >
                        {{ section.label }}
                    </p>
                    <!--
                        `DropdownMenuItem` مش مجرد لينك: هو اللي بيدّي
                        التنقّل بالأسهم والخروج بـ Escape. المرور بالماوس
                        مابيشتغلش بالكيبورد، فالقائمة لازم تفضل قائمة
                        حقيقية مش مجموعة روابط في صندوق.
                    -->
                    <DropdownMenuItem
                        v-for="item in section.items"
                        :key="item.title"
                        as-child
                        :class="
                            isActive(item)
                                ? 'bg-sidebar-accent font-medium text-sidebar-accent-foreground'
                                : ''
                        "
                        @select="flyoutOpen = false"
                    >
                        <Link :href="item.href" class="cursor-pointer">
                            <component
                                :is="item.icon"
                                class="size-4 shrink-0"
                            />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>

    <!-- قسم مفتوح العرض: بيتفتح وبيتقفل -->
    <Collapsible
        v-else
        :open="open"
        class="group/section"
        @update:open="emit('update:open', $event)"
    >
        <SidebarMenu>
            <SidebarMenuItem>
                <CollapsibleTrigger as-child>
                    <SidebarMenuButton
                        :is-active="hasActive"
                        class="font-medium transition-colors data-[active=true]:bg-sidebar-accent/60 data-[active=true]:text-sidebar-accent-foreground"
                        :data-test="`section-${section.key}`"
                        :aria-label="section.label"
                    >
                        <component :is="section.icon" />
                        <span>{{ section.label }}</span>
                        <ChevronDown
                            class="ms-auto size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]/section:rotate-180"
                        />
                    </SidebarMenuButton>
                </CollapsibleTrigger>

                <CollapsibleContent
                    class="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                >
                    <SidebarMenuSub>
                        <SidebarMenuSubItem
                            v-for="item in section.items"
                            :key="item.title"
                        >
                            <SidebarMenuSubButton
                                as-child
                                :is-active="isActive(item)"
                                class="relative transition-colors data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground data-[active=true]:shadow-soft"
                            >
                                <Link :href="item.href">
                                    <component
                                        :is="item.icon"
                                        class="size-4 shrink-0"
                                    />
                                    <span>{{ item.title }}</span>
                                    <span
                                        v-if="isActive(item)"
                                        aria-hidden="true"
                                        class="absolute inset-y-1 end-0 w-1 rounded-full bg-sidebar-primary"
                                    />
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </SidebarMenu>
    </Collapsible>
</template>
