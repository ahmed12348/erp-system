import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ErpAbilities, ErpRole } from '@/types';

/**
 * صلاحيات المستخدم الحالي.
 *
 * بتخفي أزرار الإضافة والتعديل والحذف قدام الأدوار اللي مالهاش الصلاحية.
 * ده تحسين شكلي بس — المنع الحقيقي في الـ Form Requests والـ Gates على
 * السيرفر، فحتى لو حد عدّل الـ HTML الطلب هيترفض.
 */
export function useAbilities() {
    const page = usePage();

    const can = computed<ErpAbilities>(
        () =>
            page.props.auth?.can ?? {
                manageMasterData: false,
                quickAddProduct: false,
                manageUsers: false,
            },
    );

    return {
        can,
        role: computed<ErpRole | null>(() => page.props.auth?.role ?? null),
        manageMasterData: computed(() => can.value.manageMasterData),
        quickAddProduct: computed(() => can.value.quickAddProduct),
        manageUsers: computed(() => can.value.manageUsers),
    };
}
