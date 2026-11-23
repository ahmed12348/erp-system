import { readonly, ref } from 'vue';
import { AiError, aiRequest } from '@/lib/ai';

/**
 * حالة محادثة «اسأل السيستم».
 *
 * شات واحد بيتسأل فيه أي حاجة. كان فيه تبويبات والمستخدم بيختار
 * المجال؛ المجالات لسه موجودة بس بقت ورا الستار — السيرفر بيصنّف
 * السؤال ويبعت أدوات مجاله. فمفيش هنا لا تبويبات ولا تاريخ لكل تبويب.
 *
 * التاريخ الحقيقي في جلسة السيرفر؛ اللي هنا نسخة للعرض بتتزامن أول ما
 * اللوحة تتفتح.
 *
 * الحالة على مستوى الموديول (مش جوه الكمبوننت) عشان المحادثة تفضل زي
 * ما هي وانت بتتنقّل بين الشاشات — Inertia بيبدّل الصفحة من غير ما
 * يعيد تحميل الـ JS.
 */

export type ChatMessage = {
    role: 'user' | 'assistant';
    content: string;
};

type ChatPayload = {
    starters: string[];
    messages: ChatMessage[];
    enabled: boolean;
};

const open = ref(false);
const messages = ref<ChatMessage[]>([]);
const starters = ref<string[]>([]);
const thinking = ref(false);
const error = ref<string | null>(null);
const loaded = ref(false);

export function useAskSystem() {
    /** بيجيب المحادثة والأسئلة المقترحة */
    async function load(force = false) {
        if (loaded.value && !force) {
            return;
        }

        try {
            const payload = await aiRequest<ChatPayload>('/ai/chat');

            starters.value = payload.starters;
            messages.value = payload.messages;
            loaded.value = true;
        } catch (e) {
            error.value =
                e instanceof AiError ? e.message : 'مقدرتش أجيب المحادثة.';
        }
    }

    async function openPanel() {
        open.value = true;
        error.value = null;

        await load();
    }

    function closePanel() {
        open.value = false;
    }

    async function send(question: string) {
        const text = question.trim();

        if (text === '' || thinking.value) {
            return;
        }

        error.value = null;

        // السؤال بيتعرض على طول عشان المستخدم يشوفه اتبعت فعلاً
        messages.value = [...messages.value, { role: 'user', content: text }];
        thinking.value = true;

        try {
            const payload = await aiRequest<{ reply: ChatMessage }>(
                '/ai/chat',
                { method: 'POST', body: { message: text } },
            );

            messages.value = [...messages.value, payload.reply];
        } catch (e) {
            error.value =
                e instanceof AiError
                    ? e.message
                    : 'المساعد مش راضي يرد دلوقتي. جرّب تاني.';
        } finally {
            thinking.value = false;
        }
    }

    async function reset() {
        error.value = null;

        try {
            await aiRequest('/ai/chat', { method: 'DELETE' });

            messages.value = [];
        } catch (e) {
            error.value =
                e instanceof AiError ? e.message : 'مقدرتش أمسح المحادثة.';
        }
    }

    return {
        open: readonly(open),
        messages: readonly(messages),
        starters: readonly(starters),
        thinking: readonly(thinking),
        error: readonly(error),
        openPanel,
        closePanel,
        load,
        send,
        reset,
    };
}
