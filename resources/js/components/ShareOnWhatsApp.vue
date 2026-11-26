<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { MessageCircle, Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAbilities } from '@/composables/useAbilities';
import { isMobileDevice, toWhatsAppNumber, whatsAppChatUrl } from '@/lib/phone';

/**
 * مشاركة مستند على الواتساب.
 *
 * الزرار بيفتح **شات التاجر نفسه** — مش قائمة جهات الاتصال. قبل كده
 * كان بيفتح `wa.me` من غير رقم، فالمستخدم كان لازم يدوّر على التاجر
 * في قايمته كل مرة، وده أكتر خطوة بتتكرر في اليوم.
 *
 * ومفيش API هنا: مفيش توكن ولا قالب معتمد ولا سيرفر وسيط. ده اللي
 * بيخلّيه شغّال من يوم ما ينشر.
 *
 * طريقتين حسب الجهاز:
 *
 *  - **موبايل**: `navigator.share` بملف الـ PDF نفسه لو المتصفح
 *    بيسمح، فالتاجر بيستلم الورقة كمرفق مش لينك. ولو مامشيش،
 *    بيرجع للشات.
 *  - **كمبيوتر**: شات التاجر برسالة فيها الملخّص واللينك الموقّع.
 *    مشاركة الملفات مش متاحة على الديسكتوب.
 *
 * ولما الـ Cloud API متظبّط بيظهر زرار **«إرسال مباشر»** كمان:
 * الرسالة بتروح من رقم الشركة من غير ما يتفتح أي حاجة.
 */
const props = defineProps<{
    /** الملخّص العربي اللي بيتبعت في الرسالة */
    text: string;
    /** رابط موقّع للـ PDF */
    fileUrl: string;
    fileName: string;
    /** رقم التاجر زي ما هو مسجّل — ممكن يكون فاضي */
    phone?: string | null;
    /** اسم الطرف — بيتعرض في النافذة لما الرقم ناقص */
    partyName?: string | null;
    /** التاجر أو المورد اللي الرقم هيتحفظ عليه */
    party?: { type: 'customer' | 'supplier'; id: number } | null;
    /** المستند، عشان «إرسال مباشر» */
    document?: { type: 'preparation-order' | 'invoice'; id: number } | null;
}>();

const page = usePage();
const { manageMasterData } = useAbilities();

const sharing = ref(false);
const asking = ref(false);

/** الرقم المسجّل بعد التطبيع — null يعني مفيش رقم صالح */
const number = computed(() => toWhatsAppNumber(props.phone));

const target = computed<'app' | 'web'>(() =>
    page.props.whatsapp?.target === 'web' ? 'web' : 'app',
);

/** «إرسال مباشر» بيظهر بس لما مفاتيح Cloud API متظبّطة */
const canSendDirect = computed(
    () => props.document != null && page.props.whatsapp?.direct === true,
);

// ── النافذة لما الرقم ناقص ──────────────────────────────────────

const askForm = useForm({ phone: '' });
const askError = ref<string | null>(null);

/** الرقم المكتوب في النافذة بعد التطبيع */
const typed = computed(() => toWhatsAppNumber(askForm.phone));

function ask() {
    askForm.phone = props.phone ?? '';
    askError.value = null;
    asking.value = true;
}

/** يبعت على الرقم المكتوب من غير ما يحفظه */
function sendToTyped() {
    if (typed.value === null) {
        askError.value = 'الرقم ده مش شكله رقم موبايل صالح.';

        return;
    }

    asking.value = false;
    openChat(typed.value);
}

/**
 * يحفظ الرقم على التاجر وبعدين يفتح الشات.
 *
 * الحفظ قبل الفتح عن قصد: لو فتحنا الأول، المستخدم بيسيب الصفحة
 * ويروح لواتساب والحفظ بيضيع.
 */
function saveAndSend() {
    if (typed.value === null) {
        askError.value = 'الرقم ده مش شكله رقم موبايل صالح.';

        return;
    }

    const chatNumber = typed.value;

    router.post(
        '/whatsapp/phone',
        {
            party: props.party?.type,
            id: props.party?.id,
            phone: askForm.phone,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                asking.value = false;
                openChat(chatNumber);
            },
            onError: (errors) => {
                askError.value = errors.phone ?? 'الحفظ مامشيش.';
            },
        },
    );
}

// ── الفتح والمشاركة ─────────────────────────────────────────────

function message(): string {
    return `${props.text}\n\n${new URL(props.fileUrl, window.location.origin).toString()}`;
}

function openChat(to: string) {
    window.open(
        whatsAppChatUrl(to, message(), target.value, isMobileDevice()),
        '_blank',
        'noopener',
    );
}

async function share() {
    if (number.value === null) {
        ask();

        return;
    }

    const to = number.value;

    /*
     * `canShare` لازم يتسأل بالملف نفسه: فيه متصفحات بتدعم `share`
     * للنص بس. ولو سألنا على `share` لوحده، المشاركة بتقع على
     * الديسكتوب بعد ما تكون نزّلت الملف على الفاضي.
     *
     * ومشاركة الملف بتسيب المستخدم يختار المستقبل بنفسه — مفيش
     * طريقة نقوله «ابعت للرقم ده» — فبنعملها على الموبايل بس، حيث
     * المرفق أهم من توفير خطوة الاختيار.
     */
    if (
        !isMobileDevice() ||
        typeof navigator === 'undefined' ||
        !navigator.canShare
    ) {
        openChat(to);

        return;
    }

    sharing.value = true;

    try {
        const response = await fetch(props.fileUrl);

        if (!response.ok) {
            throw new Error('تحميل الـ PDF وقع');
        }

        const file = new File([await response.blob()], props.fileName, {
            type: 'application/pdf',
        });

        if (!navigator.canShare({ files: [file] })) {
            openChat(to);

            return;
        }

        await navigator.share({ text: props.text, files: [file] });
    } catch (error) {
        /*
         * المستخدم لما بيقفل نافذة المشاركة، المتصفح بيرمي
         * `AbortError` — ده مش عطل، ده إنه غيّر رأيه. أي حاجة تانية
         * معناها إن المشاركة بالملف مامشيتش، فبنرجع للشات.
         */
        if ((error as Error)?.name !== 'AbortError') {
            openChat(to);
        }
    } finally {
        sharing.value = false;
    }
}

// ── الإرسال المباشر ─────────────────────────────────────────────

const sendingDirect = ref(false);

function sendDirect() {
    sendingDirect.value = true;

    router.post(
        '/whatsapp/send',
        {
            type: props.document?.type,
            id: props.document?.id,
            // الرقم المسجّل هو الأصل؛ السيرفر بيقراه لوحده لو فاضي
            phone: props.phone ?? '',
        },
        {
            preserveScroll: true,
            onFinish: () => {
                sendingDirect.value = false;
            },
        },
    );
}
</script>

<template>
    <Button
        variant="outline"
        :disabled="sharing"
        data-test="share-whatsapp"
        @click="share"
    >
        <Spinner v-if="sharing" class="size-4" />
        <MessageCircle v-else class="size-4" />
        واتساب
    </Button>

    <Button
        v-if="canSendDirect"
        variant="outline"
        :disabled="sendingDirect"
        data-test="send-whatsapp-direct"
        @click="sendDirect"
    >
        <Spinner v-if="sendingDirect" class="size-4" />
        <Send v-else class="size-4" />
        إرسال مباشر
    </Button>

    <!-- الرقم ناقص: نسأل عنه بدل ما نفتح واتساب على الفاضي -->
    <Dialog :open="asking" @update:open="asking = $event">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>رقم الواتساب</DialogTitle>
                <DialogDescription>
                    {{ partyName ?? 'الطرف ده' }} مالوش رقم مسجّل. اكتب الرقم
                    اللي هتبعتله.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="whatsapp_phone">الرقم</Label>
                <Input
                    id="whatsapp_phone"
                    v-model="askForm.phone"
                    dir="ltr"
                    class="text-start"
                    placeholder="01012345678"
                    data-test="whatsapp-phone-input"
                    @keyup.enter="sendToTyped"
                />
                <InputError :message="askError ?? undefined" />
                <p
                    v-if="typed"
                    class="text-xs text-muted-foreground"
                    data-test="whatsapp-normalised"
                >
                    هيتبعت على
                    <span dir="ltr">+{{ typed }}</span>
                </p>
            </div>

            <DialogFooter class="gap-2 sm:justify-start">
                <Button
                    :disabled="!typed"
                    data-test="whatsapp-send-once"
                    @click="sendToTyped"
                >
                    <MessageCircle class="size-4" />
                    افتح الشات
                </Button>
                <!-- الحفظ على التاجر لمين ليه صلاحية البيانات الأساسية بس -->
                <Button
                    v-if="party && manageMasterData"
                    variant="outline"
                    :disabled="!typed || askForm.processing"
                    data-test="whatsapp-save-phone"
                    @click="saveAndSend"
                >
                    <Spinner v-if="askForm.processing" class="size-4" />
                    احفظه على {{ partyName ?? 'الطرف' }} وابعت
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
