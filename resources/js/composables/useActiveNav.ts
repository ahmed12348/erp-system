import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, MaybeRefOrGetter } from 'vue';
import { computed, toValue } from 'vue';
import { toUrl } from '@/lib/utils';
import type { NavGroup } from '@/types';

/**
 * بيحدّد عنصر واحد بس في القائمة على إنه مفعّل.
 *
 * المطابقة بتبقى على حدود المسار (/sales أو /sales/12) — مش مجرد startsWith،
 * عشان /sales ما تفضلش مفعّلة وإحنا على /sales-returns. ولو أكتر من عنصر طابق
 * (زي /purchases و /purchases/new) بناخد الأطول، فدايماً واحد بالظبط.
 *
 * بياخد مصفوفة عادية أو ref/computed — القائمة بتتغيّر حسب صلاحيات
 * المستخدم، فلازم المطابقة تتحسب من تاني لما العناصر تتغيّر.
 */
export function useActiveNav(groups: MaybeRefOrGetter<NavGroup[]>): {
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

    const hrefs = computed(() =>
        toValue(groups).flatMap((group) =>
            group.items.map((item) =>
                (toUrl(item.href) ?? '').replace(/\/+$/, ''),
            ),
        ),
    );

    const activeHref = computed(() => {
        const matches = hrefs.value.filter(
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
