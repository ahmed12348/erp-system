<script setup lang="ts">
import { Eraser, Send, Sparkles } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useAskSystem } from '@/composables/useAskSystem';

/**
 * لوحة «اسأل السيستم».
 *
 * بتطلع من الشمال — الشريط الجانبي على اليمين في الواجهة العربية،
 * فالناحية التانية هي الفاضية.
 */

const { open, messages, starters, thinking, error, closePanel, send, reset } =
    useAskSystem();

const draft = ref('');
const thread = ref<HTMLElement | null>(null);

const isEmpty = computed(() => messages.value.length === 0);

/** النزول لآخر الكلام مع كل رسالة جديدة أو وهو بيفكّر */
async function scrollToEnd() {
    await nextTick();

    if (thread.value) {
        thread.value.scrollTop = thread.value.scrollHeight;
    }
}

watch([() => messages.value.length, thinking], scrollToEnd);
watch(open, (isOpen) => isOpen && scrollToEnd());

async function submit() {
    const question = draft.value;

    draft.value = '';

    await send(question);
}

function ask(question: string) {
    draft.value = question;

    return submit();
}

/** Enter يبعت، Shift+Enter سطر جديد */
function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();

        void submit();
    }
}
</script>

<template>
    <Sheet
        :open="open"
        @update:open="(value: boolean) => !value && closePanel()"
    >
        <SheetContent
            side="left"
            class="flex w-full flex-col gap-0 p-0 sm:max-w-md"
            data-test="ask-system-panel"
        >
            <SheetHeader class="border-b px-4 py-3 text-start">
                <SheetTitle class="flex items-center gap-2 text-base">
                    <Sparkles class="size-4 text-primary" />
                    اسأل السيستم
                </SheetTitle>
                <SheetDescription class="text-xs">
                    بيجاوب من بيانات النظام — بيقرا بس وما بيعدّلش حاجة.
                </SheetDescription>
            </SheetHeader>

            <!-- المحادثة -->
            <div
                ref="thread"
                class="flex-1 scrollbar-thin space-y-3 overflow-y-auto px-4 py-4"
            >
                <!-- الحالة الفاضية: الأسئلة المقترحة -->
                <div v-if="isEmpty && !thinking" class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        اسأل عن المخزون أو المديونيات أو المبيعات. مثلاً:
                    </p>
                    <div class="flex flex-col gap-2">
                        <button
                            v-for="starter in starters"
                            :key="starter"
                            type="button"
                            class="rounded-lg border border-border bg-card px-3 py-2 text-start text-sm transition-colors hover:border-primary/50 hover:bg-accent disabled:pointer-events-none disabled:opacity-50"
                            :disabled="thinking"
                            data-test="ask-starter"
                            @click="ask(starter)"
                        >
                            {{ starter }}
                        </button>
                    </div>
                </div>

                <div
                    v-for="(message, index) in messages"
                    :key="index"
                    class="flex"
                    :class="
                        message.role === 'user'
                            ? 'justify-start'
                            : 'justify-end'
                    "
                >
                    <div
                        class="max-w-[85%] rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap"
                        :class="
                            message.role === 'user'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground'
                        "
                        :data-test="`ask-message-${message.role}`"
                    >
                        {{ message.content }}
                    </div>
                </div>

                <!-- بيفكّر -->
                <div v-if="thinking" class="flex justify-end">
                    <div
                        class="flex items-center gap-1.5 rounded-2xl bg-muted px-3 py-3"
                        data-test="ask-thinking"
                    >
                        <span
                            v-for="dot in 3"
                            :key="dot"
                            class="size-1.5 animate-bounce rounded-full bg-muted-foreground/60"
                            :style="{
                                animationDelay: `${(dot - 1) * 0.15}s`,
                            }"
                        />
                    </div>
                </div>

                <!-- العطل جوه المحادثة مش توست، عشان السؤال يفضل على الشاشة -->
                <p
                    v-if="error"
                    class="rounded-lg bg-destructive/10 px-3 py-2 text-center text-xs text-destructive"
                    data-test="ask-error"
                >
                    {{ error }}
                </p>
            </div>

            <!-- الكتابة -->
            <div class="border-t p-3">
                <form class="flex items-end gap-2" @submit.prevent="submit">
                    <textarea
                        v-model="draft"
                        rows="1"
                        placeholder="اكتب سؤالك…"
                        maxlength="1000"
                        class="max-h-28 min-h-9 flex-1 resize-none scrollbar-thin rounded-lg border border-input bg-transparent px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-hidden"
                        data-test="ask-input"
                        @keydown="onKeydown"
                    />
                    <Button
                        type="submit"
                        size="icon"
                        class="size-9 shrink-0"
                        :disabled="thinking || draft.trim() === ''"
                        aria-label="ابعت السؤال"
                        data-test="ask-send"
                    >
                        <Send class="size-4" />
                    </Button>
                </form>

                <div class="mt-2 flex items-center justify-between">
                    <p class="text-[11px] text-muted-foreground">
                        المحادثة بتتمسح لما تعمل تسجيل خروج.
                    </p>
                    <Button
                        v-if="!isEmpty"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 gap-1 px-2 text-[11px] text-muted-foreground"
                        data-test="ask-reset"
                        @click="reset"
                    >
                        <Eraser class="size-3" />
                        محادثة جديدة
                    </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
