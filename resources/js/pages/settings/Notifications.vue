<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { BellRing, Check, X } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { aiRequest } from '@/lib/ai';
import type { BreadcrumbItem } from '@/types';

/**
 * إعدادات التنبيهات.
 *
 * جدول: صف لكل نوع تنبيه، وعمود لكل قناة. القناة اللي مفاتيحها مش
 * متظبّطة على السيرفر بتظهر مقفولة ومكتوب جنبها الناقص — بدل ما
 * المستخدم يفعّلها ومايوصلهوش حاجة ويفتكر النظام باظ.
 */

type Channel = {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
    available: boolean;
    reason: string | null;
};

type Row = {
    key: string;
    label: string;
    description: string;
    channels: Channel[];
};

const props = defineProps<{
    matrix: Row[];
    push: { publicKey: string | null; subscribed: boolean };
    whatsapp: { number: string | null; verified: boolean; available: boolean };
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (page: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: page.breadcrumbs,
    }),
});

/** الحالة المحلية: {نوع: {قناة: مفعّل}} */
const state = ref<Record<string, Record<string, boolean>>>(
    Object.fromEntries(
        props.matrix.map((row) => [
            row.key,
            Object.fromEntries(row.channels.map((c) => [c.key, c.enabled])),
        ]),
    ),
);

const form = useForm({ channels: state.value });

function toggle(type: string, channel: string, value: boolean) {
    state.value = {
        ...state.value,
        [type]: { ...state.value[type], [channel]: value },
    };
}

function save() {
    form.channels = state.value;
    form.put('/settings/notifications', { preserveScroll: true });
}

// ── إشعارات المتصفح ───────────────────────────────────────────────

const pushBusy = ref(false);
const pushOn = ref(props.push.subscribed);
const pushError = ref<string | null>(null);

/**
 * base64url → ArrayBuffer، زي ما الـ Push API عايزه.
 *
 * بنرجّع الـ buffer نفسه مش الـ Uint8Array، عشان نوع
 * `applicationServerKey` بيطلب ArrayBuffer مضمون مش أي
 * ArrayBufferLike.
 */
function decodeKey(key: string): ArrayBuffer {
    const padded = (key + '='.repeat((4 - (key.length % 4)) % 4))
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const raw = window.atob(padded);
    const bytes = new Uint8Array(new ArrayBuffer(raw.length));

    for (let i = 0; i < raw.length; i++) {
        bytes[i] = raw.charCodeAt(i);
    }

    return bytes.buffer as ArrayBuffer;
}

/**
 * الإذن بيتطلب من الزرار ده بس — عمرنا ما بنطلبه لوحدنا أول ما
 * الصفحة تفتح. المتصفحات بتعاقب على كده، والمستخدم بيرفض من غير
 * ما يفهم بيرفض إيه.
 */
async function enablePush() {
    pushBusy.value = true;
    pushError.value = null;

    try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            pushError.value = 'المتصفح ده مابيدعمش إشعارات الويب.';

            return;
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            pushError.value =
                'الإذن اترفض. لازم تسمح بالإشعارات من إعدادات المتصفح.';

            return;
        }

        const registration = await navigator.serviceWorker.register('/sw.js');
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: decodeKey(props.push.publicKey ?? ''),
        });

        await aiRequest('/notifications/push', {
            method: 'POST',
            body: JSON.parse(JSON.stringify(subscription)),
        });

        pushOn.value = true;
    } catch {
        pushError.value = 'مقدرتش أفعّل الإشعارات. جرّب تاني.';
    } finally {
        pushBusy.value = false;
    }
}

async function disablePush() {
    pushBusy.value = true;

    try {
        const registration = await navigator.serviceWorker.getRegistration();
        const subscription = await registration?.pushManager.getSubscription();

        await subscription?.unsubscribe();

        await aiRequest('/notifications/push', {
            method: 'DELETE',
            body: { endpoint: subscription?.endpoint ?? '' },
        });

        pushOn.value = false;
    } catch {
        pushError.value = 'مقدرتش أقفل الإشعارات.';
    } finally {
        pushBusy.value = false;
    }
}
</script>

<template>
    <Head title="إعدادات التنبيهات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="إعدادات التنبيهات"
            description="اختار تستقبل إيه وفين — كل مستخدم له إعداداته"
        />

        <SectionCard flush>
            <div class="scrollbar-thin overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-border text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="px-5 py-3 text-start font-medium">
                                التنبيه
                            </th>
                            <th
                                v-for="channel in matrix[0]?.channels ?? []"
                                :key="channel.key"
                                class="w-28 px-3 py-3 text-center font-medium"
                            >
                                {{ channel.label }}
                                <span
                                    v-if="!channel.available"
                                    class="block text-[10px] font-normal text-muted-foreground"
                                >
                                    مش متاح
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in matrix" :key="row.key">
                            <td class="px-5 py-3">
                                <p class="font-medium">{{ row.label }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ row.description }}
                                </p>
                            </td>
                            <td
                                v-for="channel in row.channels"
                                :key="channel.key"
                                class="px-3 py-3 text-center"
                            >
                                <Checkbox
                                    :model-value="
                                        state[row.key]?.[channel.key] ?? false
                                    "
                                    :disabled="!channel.available"
                                    :aria-label="`${row.label} — ${channel.label}`"
                                    :data-test="`toggle-${row.key}-${channel.key}`"
                                    @update:model-value="
                                        (v) =>
                                            toggle(
                                                row.key,
                                                channel.key,
                                                v === true,
                                            )
                                    "
                                />
                                <span
                                    v-if="channel.reason"
                                    class="mt-1 block text-[10px] text-muted-foreground"
                                >
                                    {{ channel.reason }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-border p-4">
                <Button
                    :disabled="form.processing"
                    data-test="save-notification-settings"
                    @click="save"
                >
                    حفظ الإعدادات
                </Button>
            </div>
        </SectionCard>

        <!-- إشعارات المتصفح -->
        <SectionCard title="إشعارات المتصفح">
            <div class="space-y-3">
                <p class="text-sm text-muted-foreground">
                    بتوصلك حتى والنظام مقفول. لازم تسمح للمتصفح مرة واحدة من
                    الزرار ده — إحنا عمرنا ما بنطلب الإذن لوحدنا.
                </p>

                <p
                    v-if="!push.publicKey"
                    class="text-sm text-muted-foreground"
                    data-test="push-unavailable"
                >
                    مفاتيح VAPID مش متظبّطة على السيرفر، فالإشعارات مقفولة.
                </p>

                <div v-else class="flex items-center gap-3">
                    <Button
                        v-if="!pushOn"
                        variant="outline"
                        :disabled="pushBusy"
                        data-test="enable-push"
                        @click="enablePush"
                    >
                        <BellRing class="size-4" />
                        فعّل إشعارات المتصفح
                    </Button>
                    <template v-else>
                        <span
                            class="flex items-center gap-1.5 text-sm text-status-good"
                        >
                            <Check class="size-4" />
                            مفعّلة على الجهاز ده
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="pushBusy"
                            data-test="disable-push"
                            @click="disablePush"
                        >
                            <X class="size-4" />
                            قفلها
                        </Button>
                    </template>
                </div>

                <p v-if="pushError" class="text-sm text-destructive">
                    {{ pushError }}
                </p>
            </div>
        </SectionCard>

        <!-- واتساب -->
        <SectionCard title="واتساب">
            <p v-if="!whatsapp.available" class="text-sm text-muted-foreground">
                مفاتيح واتساب مش متظبّطة على السيرفر، فالقناة دي مقفولة.
            </p>
            <p
                v-else-if="!whatsapp.verified"
                class="text-sm text-muted-foreground"
            >
                لازم تسجّل رقم واتساب وتأكّده من صفحة الملف الشخصي عشان
                التنبيهات توصلك عليه.
            </p>
            <p
                v-else
                class="flex items-center gap-1.5 text-sm text-status-good"
            >
                <Check class="size-4" />
                الرقم {{ whatsapp.number }} متأكّد ومتصل.
            </p>
        </SectionCard>
    </div>
</template>
