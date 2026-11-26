import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, MaybeRefOrGetter } from 'vue';
import { computed, toValue } from 'vue';
import { toUrl } from '@/lib/utils';
import type { NavItem, NavSection } from '@/types';

/** العنصر بعد ما يتفكّ لمسار و query — المطابقة بتشتغل على الاتنين */
type Candidate = {
    /** المفتاح اللي الواجهة بتقارن بيه: نفس `href` الأصلي بالظبط */
    href: string;
    path: string;
    params: [string, string][];
    matchDefault: boolean;
};

function candidate(item: NavItem): Candidate {
    const href = toUrl(item.href) ?? '';
    const [rawPath, query = ''] = href.split('?');

    return {
        href,
        path: rawPath.replace(/\/+$/, ''),
        params: [...new URLSearchParams(query).entries()],
        matchDefault: item.matchDefault === true,
    };
}

/**
 * بيحدّد عنصر واحد بس في القائمة على إنه مفعّل.
 *
 * المطابقة بتبقى على حدود المسار (/sales أو /sales/12) — مش مجرد
 * startsWith، عشان /sales ما تفضلش مفعّلة وإحنا على /sales-returns. ولو
 * أكتر من عنصر طابق (زي /purchases و /purchases/new) بناخد الأطول،
 * فدايماً واحد بالظبط.
 *
 * والـ **query** بتتحسب كمان: مرتجعات المبيعات والمشتريات على نفس
 * الصفحة بـ `?type=`، فعنصر بـ query طابقت بتكسب على أخوه اللي طابق
 * بالمسار بس. ولو مفيش query في اللينك خالص (زي `/returns/12`) بياخدها
 * العنصر المعلّم `matchDefault`.
 *
 * بياخد مصفوفة عادية أو ref/computed — القائمة بتتغيّر حسب صلاحيات
 * المستخدم، فلازم المطابقة تتحسب من تاني لما العناصر تتغيّر.
 */
export function useActiveNav(sections: MaybeRefOrGetter<NavSection[]>): {
    activeHref: ComputedRef<string | null>;
} {
    const page = usePage();

    const url = computed(() => {
        const origin =
            typeof window === 'undefined'
                ? 'http://localhost'
                : window.location.origin;

        return new URL(page.url, origin);
    });

    const currentPath = computed(
        () => url.value.pathname.replace(/\/+$/, '') || '/',
    );

    const candidates = computed<Candidate[]>(() =>
        toValue(sections).flatMap((section) => [
            ...(section.href
                ? [candidate({ title: section.label, href: section.href })]
                : []),
            ...(section.items ?? []).map(candidate),
        ]),
    );

    const activeHref = computed(() => {
        const onPath = candidates.value.filter(
            (entry) =>
                currentPath.value === entry.path ||
                currentPath.value.startsWith(`${entry.path}/`),
        );

        if (onPath.length === 0) {
            return null;
        }

        // اللي كل الـ query بتاعته موجودة في العنوان الحالي
        const matched = onPath.filter((entry) =>
            entry.params.every(
                ([key, value]) => url.value.searchParams.get(key) === value,
            ),
        );

        // عنصر بـ query صح أدق من عنصر بالمسار بس
        const withParams = matched.filter((entry) => entry.params.length > 0);

        const pool = withParams.length > 0 ? withParams : matched;

        if (pool.length === 0) {
            return null;
        }

        /*
         * لما مفيش query طابقت، العنصر المعلّم `matchDefault` هو صاحب
         * المسار — `/returns` و`/returns/12` بيروحوا لمرتجعات المبيعات
         * بدل ما مايتعلّمش حاجة.
         */
        if (withParams.length === 0) {
            const fallback = pool.find((entry) => entry.matchDefault);

            if (fallback) {
                return fallback.href;
            }
        }

        return pool.reduce((longest, entry) =>
            entry.path.length > longest.path.length ? entry : longest,
        ).href;
    });

    return { activeHref };
}
