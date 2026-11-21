<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import AskSystemPanel from '@/components/AskSystemPanel.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAskSystem } from '@/composables/useAskSystem';

/**
 * زرار «اسأل السيستم» في الشريط العلوي.
 *
 * بيختفي خالص لو المساعد مقفول من `.env` أو مفيش مفتاح API — أحسن من
 * إن المستخدم يضغط ويلاقي رسالة عطل.
 */

const page = usePage();
const { openPanel } = useAskSystem();

const enabled = computed(() => page.props.aiEnabled === true);
</script>

<template>
    <template v-if="enabled">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-9 text-muted-foreground hover:text-foreground"
                    aria-label="اسأل السيستم"
                    data-test="ask-system-button"
                    @click="openPanel"
                >
                    <Sparkles class="size-4.5" />
                </Button>
            </TooltipTrigger>
            <TooltipContent side="bottom">اسأل السيستم</TooltipContent>
        </Tooltip>

        <AskSystemPanel />
    </template>
</template>
