<script setup lang="ts">
import { MessageCircle } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

/**
 * مشاركة على الواتساب **من غير أي API**.
 *
 * مفيش توكن ولا رقم مسجّل ولا قالب معتمد ولا سيرفر وسيط: الزرار بيفتح
 * واتساب اللي على الجهاز وبيسيب المستخدم هو اللي يختار يبعت لمين. ده
 * اللي بيخلّيه شغّال من يوم ما يتنشر، من غير حساب Meta ولا مراجعة.
 *
 * طريقتين حسب الجهاز:
 *
 *  - **موبايل**: `navigator.share` بملف الـ PDF نفسه، فالتاجر بيستلم
 *    الورقة كمرفق في الشات — مش لينك يفتح متصفح. ده اللي الناس
 *    بتتوقعه فعلاً.
 *  - **كمبيوتر**: `wa.me` برسالة فيها الملخّص واللينك الموقّع. مشاركة
 *    الملفات مش متاحة على الديسكتوب، واللينك بيفتح من غير تسجيل دخول.
 *
 * ولو مشاركة الملف وقعت أو المستخدم قفلها، بنرجع لـ `wa.me` — الزرار
 * ما بيبوظش، بيرجع للطريقة التانية وخلاص.
 */
const props = defineProps<{
    /** الملخّص العربي اللي بيتبعت في الرسالة */
    text: string;
    /** رابط موقّع للـ PDF */
    fileUrl: string;
    fileName: string;
}>();

const sharing = ref(false);

/** بيفتح واتساب برسالة نصية فيها اللينك */
function openWhatsApp() {
    const message = `${props.text}\n\n${absoluteFileUrl()}`;

    window.open(
        `https://wa.me/?text=${encodeURIComponent(message)}`,
        '_blank',
        'noopener',
    );
}

function absoluteFileUrl(): string {
    return new URL(props.fileUrl, window.location.origin).toString();
}

async function share() {
    /*
     * `canShare` لازم يتسأل بالملف نفسه: فيه متصفحات بتدعم `share`
     * للنص بس. ولو سألنا على `share` لوحده، المشاركة بتقع على
     * الديسكتوب بعد ما تكون نزّلت الملف على الفاضي.
     */
    if (typeof navigator === 'undefined' || !navigator.canShare) {
        openWhatsApp();

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
            openWhatsApp();

            return;
        }

        await navigator.share({ text: props.text, files: [file] });
    } catch (error) {
        /*
         * المستخدم لما بيقفل نافذة المشاركة، المتصفح بيرمي
         * `AbortError` — ده مش عطل، ده إنه غيّر رأيه. أي حاجة تانية
         * معناها إن المشاركة بالملف مامشيتش، فبنرجع للطريقة التانية.
         */
        if ((error as Error)?.name !== 'AbortError') {
            openWhatsApp();
        }
    } finally {
        sharing.value = false;
    }
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
</template>
