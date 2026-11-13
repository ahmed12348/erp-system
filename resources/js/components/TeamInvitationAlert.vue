<script setup lang="ts">
import { Info } from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { TeamInvitationContext } from '@/types';

type Props = {
    invitation: TeamInvitationContext;
    action: 'login' | 'register';
};

const props = defineProps<Props>();

const message = computed(() =>
    props.action === 'login'
        ? `سجّل الدخول للانضمام إلى فريق "${props.invitation.teamName}".`
        : `أنشئ حسابًا للانضمام إلى فريق "${props.invitation.teamName}".`,
);
</script>

<template>
    <div data-test="team-invitation-alert">
        <Alert
            class="border-primary/25 bg-accent text-accent-foreground [&>svg]:text-primary"
        >
            <Info class="size-4" />
            <AlertDescription class="text-accent-foreground">
                {{ message }}
            </AlertDescription>
        </Alert>
    </div>
</template>
