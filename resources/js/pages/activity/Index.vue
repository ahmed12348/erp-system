<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { History, Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatorMeta } from '@/components/Pagination.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Input } from '@/components/ui/input';
import { formatDateShort } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';

/**
 * سجل النشاط — مين عمل إيه وإمتى.
 *
 * السطر الواحد بيقول الحركة في جملة، والتعديلات بتعرض «قبل ← بعد» لكل
 * حقل اتغيّر، والدخول بيعرض الـ IP والمتصفح.
 */

type Change = { field: string; from: string; to: string };

type Row = {
    id: number;
    at: string | null;
    user: string | null;
    log: string;
    logLabel: string;
    event: string;
    eventLabel: string;
    subject: string | null;
    ip: string | null;
    agent: string | null;
    email: string | null;
    changes: Change[];
};

const props = defineProps<{
    rows: { data: Row[] } & PaginatorMeta;
    filters: Record<string, string | undefined>;
    users: { id: number; name: string }[];
    logs: { value: string; label: string }[];
    events: { value: string; label: string }[];
    retentionMonths: number;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

const filters = reactive({
    user_id: props.filters.user_id ?? '',
    log: props.filters.log ?? '',
    event: props.filters.event ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

function apply() {
    router.get(
        '/activity',
        Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

watchDebounced(() => [filters.from, filters.to], apply, { debounce: 400 });

/** المتصفح من الـ user agent — من غير مكتبة، الكلمات المميّزة كفاية */
function browser(agent: string | null): string {
    if (!agent) {
        return '—';
    }

    const known = [
        ['Edg/', 'Edge'],
        ['OPR/', 'Opera'],
        ['Chrome/', 'Chrome'],
        ['Firefox/', 'Firefox'],
        ['Safari/', 'Safari'],
    ] as const;

    const match = known.find(([needle]) => agent.includes(needle));
    const platform = agent.includes('Android')
        ? 'أندرويد'
        : agent.includes('iPhone') || agent.includes('iPad')
          ? 'آيفون'
          : agent.includes('Windows')
            ? 'ويندوز'
            : agent.includes('Mac')
              ? 'ماك'
              : '';

    return match
        ? `${match[1]}${platform ? ` — ${platform}` : ''}`
        : 'متصفح تاني';
}

const SELECT_CLASS =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm';
</script>

<template>
    <Head title="سجل النشاط" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="سجل النشاط"
            :description="`كل حركة في النظام بتتسجّل، وبتتحفظ ${retentionMonths} شهر`"
        />

        <SectionCard flush>
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="filters.user_id"
                        :class="SELECT_CLASS"
                        data-test="activity-user"
                        @change="apply"
                    >
                        <option value="">كل المستخدمين</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }}
                        </option>
                    </select>

                    <select
                        v-model="filters.event"
                        :class="SELECT_CLASS"
                        data-test="activity-event"
                        @change="apply"
                    >
                        <option value="">كل الحركات</option>
                        <option
                            v-for="e in events"
                            :key="e.value"
                            :value="e.value"
                        >
                            {{ e.label }}
                        </option>
                    </select>

                    <select
                        v-model="filters.log"
                        :class="SELECT_CLASS"
                        data-test="activity-log"
                        @change="apply"
                    >
                        <option value="">كل الكيانات</option>
                        <option
                            v-for="l in logs"
                            :key="l.value"
                            :value="l.value"
                        >
                            {{ l.label }}
                        </option>
                    </select>

                    <Input
                        v-model="filters.from"
                        type="date"
                        class="w-36"
                        aria-label="من تاريخ"
                        data-test="activity-from"
                    />
                    <Input
                        v-model="filters.to"
                        type="date"
                        class="w-36"
                        aria-label="إلى تاريخ"
                        data-test="activity-to"
                    />
                </div>
            </template>

            <div v-if="rows.data.length" class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-3 text-start font-medium">
                                التاريخ
                            </th>
                            <th class="px-5 py-3 text-start font-medium">
                                المستخدم
                            </th>
                            <th class="px-5 py-3 text-start font-medium">
                                الحركة
                            </th>
                            <th class="px-5 py-3 text-start font-medium">
                                التفاصيل
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in rows.data"
                            :key="row.id"
                            class="transition-colors hover:bg-accent/60"
                            data-test="activity-row"
                        >
                            <td
                                class="px-5 py-3 align-top whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ row.at ?? '—' }}
                            </td>
                            <td class="px-5 py-3 align-top whitespace-nowrap">
                                {{ row.user ?? '—' }}
                            </td>
                            <td class="px-5 py-3 align-top whitespace-nowrap">
                                <span class="font-medium">{{
                                    row.eventLabel
                                }}</span>
                                <span class="text-muted-foreground">
                                    {{ row.logLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <p v-if="row.subject" class="font-medium">
                                    {{ row.subject }}
                                </p>

                                <!-- الدخول: الجهاز اللي جه منه -->
                                <p
                                    v-if="row.log === 'auth'"
                                    class="text-xs text-muted-foreground"
                                >
                                    <span v-if="row.email"
                                        >{{ row.email }} —
                                    </span>
                                    {{ row.ip ?? '—' }} ·
                                    {{ browser(row.agent) }}
                                </p>

                                <!-- التعديلات: قبل ← بعد -->
                                <ul
                                    v-if="row.changes.length"
                                    class="mt-1 space-y-0.5 text-xs text-muted-foreground"
                                >
                                    <li
                                        v-for="(change, i) in row.changes"
                                        :key="i"
                                    >
                                        <span class="font-medium">{{
                                            change.field
                                        }}</span>
                                        <span class="tabular-nums">
                                            {{ change.from }} ←
                                            {{ change.to }}
                                        </span>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                :icon="History"
                title="مفيش نشاط في النتيجة دي"
                description="جرّب تغيّر الفلتر أو توسّع المدة."
            />
        </SectionCard>

        <Pagination :meta="rows" />
    </div>
</template>
