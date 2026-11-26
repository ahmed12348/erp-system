import type { ComputedRef, Ref } from 'vue';
import { computed, ref, watch } from 'vue';
import type { NavSection } from '@/types';

const STORAGE_KEY = 'sidebar_sections';

function read(): string[] | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (raw === null) {
            return null;
        }

        const parsed: unknown = JSON.parse(raw);

        return Array.isArray(parsed)
            ? parsed.filter((key): key is string => typeof key === 'string')
            : null;
    } catch {
        // متصفح بيمنع التخزين، أو قيمة بايظة — نبدأ من الأول
        return null;
    }
}

/**
 * الأقسام المفتوحة في القائمة الجانبية.
 *
 * بتتخزّن في `localStorage` عشان المستخدم ما يفضلش يفتح نفس القسم كل
 * مرة. أول مرة (مفيش حاجة متخزّنة) بيتفتح **قسم الصفحة الحالية بس** —
 * فتح الكل بيرجّعنا لنفس مشكلة العشرين عنصر اللي بندوّر فيها.
 *
 * ولما المستخدم يروح لصفحة في قسم مقفول، القسم بيتفتح لوحده: إنك تبقى
 * في صفحة والقائمة مش مبيّنة إنت فين حاجة مربكة. بس بيفضل يقدر يقفله
 * بإيده بعد كده — الفتح التلقائي بيحصل عند **تغيّر** الصفحة بس.
 */
export function useSidebarSections(
    sections: ComputedRef<NavSection[]>,
    activeHref: ComputedRef<string | null>,
): {
    isOpen: (key: string) => boolean;
    toggle: (key: string, value: boolean) => void;
} {
    /** القسم اللي فيه الصفحة المفتوحة دلوقتي */
    const activeKey = computed<string | null>(
        () =>
            sections.value.find((section) =>
                (section.items ?? []).some(
                    (item) => item.href === activeHref.value,
                ),
            )?.key ?? null,
    );

    const open: Ref<string[]> = ref(
        read() ?? (activeKey.value === null ? [] : [activeKey.value]),
    );

    function isOpen(key: string): boolean {
        return open.value.includes(key);
    }

    function toggle(key: string, value: boolean): void {
        const without = open.value.filter((entry) => entry !== key);

        open.value = value ? [...without, key] : without;
    }

    // صفحة جديدة في قسم مقفول = القسم بيتفتح
    watch(activeKey, (key) => {
        if (key !== null && !open.value.includes(key)) {
            open.value = [...open.value, key];
        }
    });

    watch(open, (value) => {
        if (typeof window === 'undefined') {
            return;
        }

        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
        } catch {
            // وضع التصفّح الخفي بيرمي هنا — القائمة بتشتغل عادي من غير حفظ
        }
    });

    return { isOpen, toggle };
}
