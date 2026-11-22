import { computed, readonly, ref } from 'vue';
import { AiError, aiRequest } from '@/lib/ai';

/**
 * حالة محادثة «اسأل السيستم».
 *
 * اللوحة فيها تبويبات (وكلاء)، كل واحد بتاريخه المستقل. التاريخ
 * الحقيقي في جلسة السيرفر؛ اللي هنا نسخة للعرض بتتزامن أول ما
 * التبويب يتفتح.
 *
 * الحالة على مستوى الموديول (مش جوه الكمبوننت) عشان المحادثة تفضل زي
 * ما هي وانت بتتنقّل بين الشاشات — Inertia بيبدّل الصفحة من غير ما
 * يعيد تحميل الـ JS.
 */

export type ChatMessage = {
    role: 'user' | 'assistant';
    content: string;
};

export type AgentTab = {
    key: string;
    label: string;
    description: string;
    starters: string[];
};

type ChatPayload = {
    agents: AgentTab[];
    agent: string | null;
    messages: ChatMessage[];
    enabled: boolean;
};

const open = ref(false);
const agents = ref<AgentTab[]>([]);
const active = ref<string | null>(null);
const thinking = ref(false);
const error = ref<string | null>(null);
const loaded = ref(false);

/** تاريخ كل تبويب على حدة */
const threads = ref<Record<string, ChatMessage[]>>({});

export function useAskSystem() {
    const messages = computed<ChatMessage[]>(() =>
        active.value ? (threads.value[active.value] ?? []) : [],
    );

    const current = computed<AgentTab | null>(
        () => agents.value.find((a) => a.key === active.value) ?? null,
    );

    /** بيجيب التبويبات ومحادثة التبويب المطلوب */
    async function load(agent?: string, force = false) {
        if (loaded.value && !force && !agent) {
            return;
        }

        try {
            const query = agent ? `?agent=${encodeURIComponent(agent)}` : '';
            const payload = await aiRequest<ChatPayload>(`/ai/chat${query}`);

            agents.value = payload.agents;
            active.value = payload.agent;
            loaded.value = true;

            if (payload.agent) {
                threads.value = {
                    ...threads.value,
                    [payload.agent]: payload.messages,
                };
            }
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

    /** بيبدّل التبويب — وبيجيب تاريخه أول مرة بس */
    async function selectAgent(key: string) {
        if (key === active.value || thinking.value) {
            return;
        }

        active.value = key;
        error.value = null;

        if (threads.value[key] === undefined) {
            await load(key, true);
        }
    }

    async function send(question: string) {
        const text = question.trim();
        const agent = active.value;

        if (text === '' || thinking.value || !agent) {
            return;
        }

        error.value = null;

        // السؤال بيتعرض على طول عشان المستخدم يشوفه اتبعت فعلاً
        threads.value = {
            ...threads.value,
            [agent]: [
                ...(threads.value[agent] ?? []),
                { role: 'user', content: text },
            ],
        };

        thinking.value = true;

        try {
            const payload = await aiRequest<{ reply: ChatMessage }>(
                '/ai/chat',
                { method: 'POST', body: { message: text, agent } },
            );

            threads.value = {
                ...threads.value,
                [agent]: [...(threads.value[agent] ?? []), payload.reply],
            };
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
        const agent = active.value;

        if (!agent) {
            return;
        }

        error.value = null;

        try {
            await aiRequest(`/ai/chat?agent=${encodeURIComponent(agent)}`, {
                method: 'DELETE',
            });

            threads.value = { ...threads.value, [agent]: [] };
        } catch (e) {
            error.value =
                e instanceof AiError ? e.message : 'مقدرتش أمسح المحادثة.';
        }
    }

    return {
        open: readonly(open),
        agents: readonly(agents),
        active: readonly(active),
        current,
        messages,
        thinking: readonly(thinking),
        error: readonly(error),
        openPanel,
        closePanel,
        selectAgent,
        load,
        send,
        reset,
    };
}
