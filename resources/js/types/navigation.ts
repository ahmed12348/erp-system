import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /**
     * العنصر ده هو صاحب المسار لما مفيش أخ ليه طابق بالـ query.
     *
     * مرتجعات المبيعات والمشتريات على نفس الصفحة بـ `?type=`، فصفحة
     * `/returns` من غير `type` (والصفحات اللي جواها زي `/returns/12`)
     * لازم حد يمسكها — وإلا مفيش حاجة بتتعلّم في القائمة.
     */
    matchDefault?: boolean;
};

/**
 * قسم في القائمة الجانبية.
 *
 * القسم إما **رابط لوحده** (`href` من غير `items` — زي لوحة التحكم
 * والتقارير) أو **مجموعة بتتفتح وتتقفل** (`items`). الاتنين في نوع
 * واحد عشان ترتيب القائمة يفضل قايمة واحدة مقروءة من فوق لتحت، مش
 * قايمتين متفرّقتين لازم تتخيّل تداخلهم.
 */
export type NavSection = {
    /** مفتاح ثابت — بيتخزّن في localStorage، فما ينفعش يتغيّر مع الترجمة */
    key: string;
    label: string;
    icon: LucideIcon;
    href?: NonNullable<InertiaLinkProps['href']>;
    items?: NavItem[];
};
