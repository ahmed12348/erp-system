<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';

export type PaginatorMeta = {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{ meta: PaginatorMeta }>();

/** أرقام الصفحات بس — من غير "التالي" و"السابق" اللي بييجوا من لارافيل */
const pageLinks = computed(() =>
    props.meta.links.filter((link) => /^\d+$/.test(link.label)),
);
</script>

<template>
    <nav
        v-if="meta.total > 0"
        class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-5 py-3"
        aria-label="تنقّل بين الصفحات"
    >
        <p class="text-xs text-muted-foreground">
            عرض {{ formatNumber(meta.from ?? 0) }}–{{
                formatNumber(meta.to ?? 0)
            }}
            من {{ formatNumber(meta.total) }}
        </p>

        <div v-if="meta.last_page > 1" class="flex items-center gap-1">
            <!-- في الواجهة العربية السهم اللي على اليمين بيرجّع للصفحة السابقة -->
            <Link
                v-if="meta.prev_page_url"
                :href="meta.prev_page_url"
                class="flex size-8 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                aria-label="الصفحة السابقة"
                preserve-scroll
            >
                <ChevronRight class="size-4" />
            </Link>

            <Link
                v-for="link in pageLinks"
                :key="link.label"
                :href="link.url ?? '#'"
                class="flex size-8 items-center justify-center rounded-md border text-sm tabular-nums transition-colors"
                :class="
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                "
                preserve-scroll
            >
                {{ formatNumber(link.label) }}
            </Link>

            <Link
                v-if="meta.next_page_url"
                :href="meta.next_page_url"
                class="flex size-8 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                aria-label="الصفحة التالية"
                preserve-scroll
            >
                <ChevronLeft class="size-4" />
            </Link>
        </div>
    </nav>
</template>
