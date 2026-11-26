<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { BellRing, Check, Mail, Send, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { aiRequest } from '@/lib/ai';
import type { BreadcrumbItem } from '@/types';

/**
 * إعدادات التنبيهات.
 *
 * جدول: صف لكل نوع تنبيه، وعمود لكل قناة. القناة اللي مفاتيحها مش
 * متظبّطة على السيرفر مابتظهرش خالص — لا عمود ولا قسم. خيار معطّل
 * بيخلّي المستخدم يسأل «ليه ده مقفول؟»، واللي مش موجود مابيسألش.
 *
 * يعني على سيرفر من غير أي إعداد، الشاشة دي بتبقى عمود واحد: الجرس.
 */

type Channel = {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
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
    whatsappShare: { target: 'app' | 'web'; directAvailable: boolean };
    alertEmails: {
        manageable: boolean;
        mailConfigured: boolean;
        recipients: {
            id: number;
            email: string;
            name: string | null;
            isActive: boolean;
        }[];
    };
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
/*
 * «فتح واتساب على» — تفضيل شخصي مش إعداد نظام.
 *
 * بيظهر دايمًا: فتح شات `wa.me` شغّال من غير أي مفاتيح، والسؤال هنا
 * هو التطبيق ولا الويب. الموبايل بياخد التطبيق دايمًا مهما كان
 * المحفوظ هنا، لأن واتساب ويب على الموبايل بيقول لك «افتح على
 * الكمبيوتر» وخلاص.
 */
const whatsappTarget = ref<'app' | 'web'>(props.whatsappShare.target);

function saveWhatsappTarget(value: 'app' | 'web') {
    whatsappTarget.value = value;

    router.put(
        '/settings/whatsapp-target',
        { target: value },
        { preserveScroll: true },
    );
}

/*
 * إيميلات التنبيهات — عناوين بتستقبل تنبيهات المخزون من غير ما تكون
 * مستخدمين في النظام. صاحب المحل مش بالضرورة بيفتح النظام كل يوم.
 */
const emailForm = useForm({ email: '', name: '' });
const testForm = useForm({ email: '' });

function addEmail() {
    emailForm.post('/settings/alert-emails', {
        preserveScroll: true,
        onSuccess: () => emailForm.reset(),
    });
}

function toggleEmail(recipient: { id: number; isActive: boolean }) {
    router.put(
        `/settings/alert-emails/${recipient.id}`,
        { is_active: !recipient.isActive },
        { preserveScroll: true },
    );
}

function removeEmail(recipient: { id: number; email: string }) {
    if (
        !window.confirm(`هتشيل ${recipient.email} من إيميلات التنبيهات. تمام؟`)
    ) {
        return;
    }

    router.delete(`/settings/alert-emails/${recipient.id}`, {
        preserveScroll: true,
    });
}

/** إرسال فعلي فوري — عشان يتأكد إن SMTP شغّال */
function sendTest() {
    testForm.post('/settings/alert-emails/test', { preserveScroll: true });
}
</script>

<template>
    <Head title="إعدادات التنبيهات" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            title="إعدادات التنبيهات"
            description="اختار تستقبل إيه وفين — جرس النظام شغّال دايمًا"
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

        <!-- إشعارات المتصفح — بتختفي خالص من غير مفاتيح VAPID -->
        <SectionCard v-if="push.publicKey" title="إشعارات المتصفح">
            <div class="space-y-3">
                <p class="text-sm text-muted-foreground">
                    بتوصلك حتى والنظام مقفول. لازم تسمح للمتصفح مرة واحدة من
                    الزرار ده — إحنا عمرنا ما بنطلب الإذن لوحدنا.
                </p>

                <div class="flex items-center gap-3">
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

        <!-- واتساب — بتختفي خالص من غير مفاتيح WHATSAPP_ -->
        <SectionCard v-if="whatsapp.available" title="واتساب">
            <p v-if="!whatsapp.verified" class="text-sm text-muted-foreground">
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

        <!-- مشاركة الواتساب — تفضيل شخصي، شغّال من غير أي مفاتيح -->
        <SectionCard
            title="مشاركة على واتساب"
            description="زرار «واتساب» على الفواتير وطلبات التحضير بيفتح الشات فين"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <button
                    v-for="option in [
                        {
                            value: 'app' as const,
                            label: 'التطبيق',
                            hint: 'بيفتح واتساب المنصّب على الجهاز',
                        },
                        {
                            value: 'web' as const,
                            label: 'واتساب ويب',
                            hint: 'بيفتح تبويب على web.whatsapp.com',
                        },
                    ]"
                    :key="option.value"
                    type="button"
                    class="flex flex-col items-start gap-1 rounded-lg border px-4 py-3 text-start transition-colors"
                    :class="
                        whatsappTarget === option.value
                            ? 'border-primary bg-primary/5'
                            : 'border-border hover:bg-accent'
                    "
                    :data-test="`whatsapp-target-${option.value}`"
                    :aria-pressed="whatsappTarget === option.value"
                    @click="saveWhatsappTarget(option.value)"
                >
                    <span class="flex items-center gap-1.5 text-sm font-medium">
                        <Check
                            v-if="whatsappTarget === option.value"
                            class="size-4 text-primary"
                        />
                        {{ option.label }}
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ option.hint }}
                    </span>
                </button>
            </div>

            <p class="mt-3 text-xs text-muted-foreground">
                على الموبايل بيفتح التطبيق دايمًا — واتساب ويب على الموبايل
                بيقول لك «افتح على الكمبيوتر» وخلاص.
            </p>

            <p
                v-if="whatsappShare.directAvailable"
                class="mt-2 flex items-center gap-1.5 text-xs text-status-good"
                data-test="whatsapp-direct-available"
            >
                <Check class="size-3.5" />
                الـ Cloud API متظبّط، فزرار «إرسال مباشر» ظاهر كمان على
                المستندات.
            </p>
        </SectionCard>

        <!-- إيميلات التنبيهات — للمدير بس -->
        <SectionCard
            v-if="alertEmails.manageable"
            title="إيميلات التنبيهات"
            description="عناوين بتوصلها تنبيهات المخزون حتى لو مش مستخدمين في النظام"
        >
            <!-- من غير MAIL_ مفيش إيميل أصلاً — لازم يتقال بصراحة -->
            <p
                v-if="!alertEmails.mailConfigured"
                class="mb-4 rounded-lg border border-status-warning/40 bg-status-warning/10 px-4 py-3 text-sm text-status-warning"
                data-test="mail-not-configured"
            >
                إعدادات الإيميل (MAIL_) مش متظبّطة في .env — العناوين دي هتتحفظ
                بس مش هيوصلها حاجة لحد ما تتظبّط.
            </p>

            <ul class="divide-y divide-border rounded-lg border border-border">
                <li
                    v-for="recipient in alertEmails.recipients"
                    :key="recipient.id"
                    class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                >
                    <span class="flex items-center gap-2">
                        <Mail class="size-4 text-muted-foreground" />
                        <span
                            dir="ltr"
                            :class="{ 'opacity-50': !recipient.isActive }"
                        >
                            {{ recipient.email }}
                        </span>
                        <span
                            v-if="recipient.name"
                            class="text-xs text-muted-foreground"
                        >
                            {{ recipient.name }}
                        </span>
                        <span
                            v-if="!recipient.isActive"
                            class="rounded-md bg-muted px-2 py-0.5 text-xs"
                        >
                            موقوف
                        </span>
                    </span>

                    <span class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="sm"
                            :data-test="`toggle-email-${recipient.id}`"
                            @click="toggleEmail(recipient)"
                        >
                            {{ recipient.isActive ? 'إيقاف' : 'تفعيل' }}
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :data-test="`delete-email-${recipient.id}`"
                            @click="removeEmail(recipient)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </span>
                </li>

                <li
                    v-if="!alertEmails.recipients.length"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    مفيش إيميلات مضافة.
                </li>
            </ul>

            <form
                class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                @submit.prevent="addEmail"
            >
                <div class="space-y-1.5">
                    <Label for="alert_email">الإيميل</Label>
                    <Input
                        id="alert_email"
                        v-model="emailForm.email"
                        type="email"
                        dir="ltr"
                        class="text-start"
                        placeholder="name@example.com"
                        data-test="alert-email-input"
                    />
                    <InputError :message="emailForm.errors.email" />
                </div>
                <div class="space-y-1.5">
                    <Label for="alert_email_name">الاسم (اختياري)</Label>
                    <Input
                        id="alert_email_name"
                        v-model="emailForm.name"
                        placeholder="صاحب المحل"
                    />
                </div>
                <div class="flex items-end">
                    <Button
                        type="submit"
                        :disabled="emailForm.processing"
                        data-test="add-alert-email"
                    >
                        <Spinner v-if="emailForm.processing" class="size-4" />
                        إضافة
                    </Button>
                </div>
            </form>

            <!-- إرسال تجريبي — بيتبعت فورًا مش في الطابور -->
            <div class="mt-6 border-t border-border pt-4">
                <form
                    class="grid gap-3 sm:grid-cols-[1fr_auto]"
                    @submit.prevent="sendTest"
                >
                    <div class="space-y-1.5">
                        <Label for="test_email">إرسال إيميل تجريبي</Label>
                        <Input
                            id="test_email"
                            v-model="testForm.email"
                            type="email"
                            dir="ltr"
                            class="text-start"
                            placeholder="اكتب إيميل وجرّب"
                            data-test="test-email-input"
                        />
                        <InputError :message="testForm.errors.email" />
                        <!-- عطل SMTP بيرجع تحت المفتاح ده مش تحت email -->
                        <InputError
                            :message="
                                (testForm.errors as Record<string, string>)
                                    .test_email
                            "
                        />
                    </div>
                    <div class="flex items-end">
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="testForm.processing"
                            data-test="send-test-email"
                        >
                            <Spinner
                                v-if="testForm.processing"
                                class="size-4"
                            />
                            <Send v-else class="size-4" />
                            إرسال إيميل تجريبي
                        </Button>
                    </div>
                </form>
                <p class="mt-2 text-xs text-muted-foreground">
                    بيتبعت على طول مش من خلال الطابور، عشان تعرف فورًا لو
                    إعدادات SMTP فيها مشكلة.
                </p>
            </div>
        </SectionCard>
    </div>
</template>
