<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell, Check, Settings } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { aiRequest } from '@/lib/ai';

/**
 * جرس التنبيهات.
 *
 * غير المقروء فوق، والضغط على التنبيه بيفتح الصفحة بتاعته ويعلّمه
 * مقروء. العدّاد بيتحدّث لما اللوحة تتفتح — مش بـ polling، عشان
 * ما نحمّلش الاستضافة المشتركة طلبات كل شوية من غير داعي.
 */

type Row = {
    id: string;
    type: string | null;
    title: string;
    body: string;
    url: string | null;
    read: boolean;
    at: string | null;
};

const rows = ref<Row[]>([]);
const unread = ref(0);
const open = ref(false);
const loading = ref(false);

const label = computed(() =>
    unread.value > 0 ? `${unread.value} تنبيه جديد` : 'التنبيهات',
);

async function load() {
    loading.value = true;

    try {
        const payload = await aiRequest<{
            notifications: Row[];
            unread: number;
        }>('/notifications');

        rows.value = payload.notifications;
        unread.value = payload.unread;
    } catch {
        // الجرس مش يستاهل رسالة خطأ — بيفضل بعدّاده القديم
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function onOpen(value: boolean) {
    open.value = value;

    if (value) {
        await load();
    }
}

async function markAll() {
    await aiRequest('/notifications/read', { method: 'POST', body: {} });

    rows.value = rows.value.map((row) => ({ ...row, read: true }));
    unread.value = 0;
}

async function openRow(row: Row) {
    if (!row.read) {
        await aiRequest('/notifications/read', {
            method: 'POST',
            body: { id: row.id },
        });

        unread.value = Math.max(0, unread.value - 1);
    }

    if (row.url) {
        open.value = false;
        router.visit(row.url);
    }
}
</script>

<template>
    <DropdownMenu :open="open" @update:open="onOpen">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative size-9 text-muted-foreground hover:text-foreground"
                :aria-label="label"
                data-test="notification-bell"
            >
                <Bell class="size-4.5" />
                <span
                    v-if="unread > 0"
                    class="absolute -end-0.5 -top-0.5 flex min-w-4.5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4.5 font-semibold text-destructive-foreground tabular-nums"
                    data-test="notification-count"
                >
                    {{ unread > 99 ? '99+' : unread }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b px-3 py-2">
                <span class="text-sm font-medium">التنبيهات</span>
                <div class="flex items-center gap-1">
                    <button
                        v-if="unread > 0"
                        type="button"
                        class="flex items-center gap-1 rounded px-1.5 py-1 text-[11px] text-muted-foreground transition-colors hover:text-foreground"
                        data-test="notification-mark-all"
                        @click.stop="markAll"
                    >
                        <Check class="size-3" />
                        علّم الكل مقروء
                    </button>
                    <a
                        href="/settings/notifications"
                        class="rounded p-1 text-muted-foreground transition-colors hover:text-foreground"
                        aria-label="إعدادات التنبيهات"
                    >
                        <Settings class="size-3.5" />
                    </a>
                </div>
            </div>

            <div class="max-h-96 scrollbar-thin overflow-y-auto">
                <p
                    v-if="!rows.length && !loading"
                    class="px-3 py-8 text-center text-sm text-muted-foreground"
                >
                    مفيش تنبيهات لسه.
                </p>

                <button
                    v-for="row in rows"
                    :key="row.id"
                    type="button"
                    class="flex w-full flex-col gap-0.5 border-b border-border/60 px-3 py-2.5 text-start transition-colors last:border-0 hover:bg-accent"
                    :class="!row.read ? 'bg-primary/5' : ''"
                    data-test="notification-row"
                    @click="openRow(row)"
                >
                    <span class="flex items-center gap-1.5">
                        <span
                            v-if="!row.read"
                            class="size-1.5 shrink-0 rounded-full bg-primary"
                        />
                        <span class="text-sm font-medium">{{ row.title }}</span>
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ row.body }}
                    </span>
                    <span
                        v-if="row.at"
                        class="text-[10px] text-muted-foreground"
                    >
                        {{ row.at }}
                    </span>
                </button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
