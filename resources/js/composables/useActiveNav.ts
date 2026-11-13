import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { toUrl } from '@/lib/utils';
import type { NavGroup } from '@/types';

/**
 * بيحدّد عنصر واحد بس في القائمة على إنه مفعّل.
 *
 * المطابقة بتبقى على حدود المسار (/sales أو /sales/12) — مش مجرد startsWith،
 * عشان /sales ما تفضلش مفعّلة وإحنا على /sales-returns. ولو أكتر من عنصر طابق
 * (زي /purchases و /purchases/new) بناخد الأطول، فدايماً واحد بالظبط.
 */
export function useActiveNav(groups: NavGroup[]): {
    activeHref: ComputedRef<string | null>;
} {
    const page = usePage();

    const currentPath = computed(() => {
        const origin =
            typeof window === 'undefined'
                ? 'http://localhost'
                : window.location.origin;

        return new URL(page.url, origin).pathname.replace(/\/+$/, '') || '/';
    });

    const hrefs = groups.flatMap((group) =>
        group.items.map((item) => (toUrl(item.href) ?? '').replace(/\/+$/, '')),
    );

    const activeHref = computed(() => {
        const matches = hrefs.filter(
            (href) =>
                currentPath.value === href ||
                currentPath.value.startsWith(`${href}/`),
        );

        if (matches.length === 0) {
            return null;
        }

        return matches.reduce((longest, href) =>
            href.length > longest.length ? href : longest,
        );
    });

    return { activeHref };
}
