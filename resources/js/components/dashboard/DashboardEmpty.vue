<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Plus, ShoppingCart, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { formatNumber } from '@/lib/format';

export type SetupStep = {
    key: string;
    label: string;
    href: string;
    cta: string;
    done: boolean;
};

const props = defineProps<{
    steps: SetupStep[];
}>();

const doneCount = computed(() => props.steps.filter((s) => s.done).length);

/*
 * الزراير: الشراء الأول لأنه هو اللي بيدخّل البضاعة المخزن، وبعده البيع.
 * لو الشراء اتعمل خلاص، البيع بيبقى هو الإجراء الأساسي.
 */
const purchaseDone = computed(
    () => props.steps.find((step) => step.key === 'purchases')?.done ?? false,
);

const primary = computed(() =>
    purchaseDone.value
        ? { label: 'فاتورة بيع جديدة', href: '/sales/create' }
        : { label: 'فاتورة شراء جديدة', href: '/purchases/create' },
);

const secondary = computed(() =>
    purchaseDone.value
        ? { label: 'فاتورة شراء جديدة', href: '/purchases/create' }
        : { label: 'فاتورة بيع جديدة', href: '/sales/create' },
);
</script>

<template>
    <section
        class="rounded-xl border border-border bg-card p-6 shadow-soft sm:p-10"
        data-test="dashboard-empty"
    >
        <div class="mx-auto max-w-xl text-center">
            <span
                class="mx-auto flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary"
            >
                <Sparkles class="size-6" />
            </span>

            <h2 class="mt-4 text-xl font-bold">
                أهلًا بيك في نظام إدارة التوزيع
            </h2>

            <p class="mt-2 text-sm text-muted-foreground">
                النظام جاهز ولسه مافيش حركة. أول ما تسجّل فاتورة شراء أو بيع،
                لوحة التحكم هتتملى بالمبيعات ومديونيات التجار وقيمة المخزون.
            </p>

            <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                <Button as-child>
                    <Link :href="primary.href">
                        <Plus class="size-4" />
                        {{ primary.label }}
                    </Link>
                </Button>

                <Button as-child variant="outline">
                    <Link :href="secondary.href">
                        <ShoppingCart class="size-4" />
                        {{ secondary.label }}
                    </Link>
                </Button>
            </div>
        </div>

        <!-- خطوات التشغيل -->
        <div class="mx-auto mt-8 max-w-xl">
            <div
                class="mb-3 flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>خطوات التشغيل</span>
                <span class="tabular-nums">
                    {{ formatNumber(doneCount) }} من
                    {{ formatNumber(steps.length) }}
                </span>
            </div>

            <ol class="divide-y divide-border rounded-lg border border-border">
                <li
                    v-for="(step, index) in steps"
                    :key="step.key"
                    class="flex items-center gap-3 px-4 py-3"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                        :class="
                            step.done
                                ? 'bg-status-good/15 text-status-good'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        <Check v-if="step.done" class="size-3.5" />
                        <template v-else>{{
                            formatNumber(index + 1)
                        }}</template>
                    </span>

                    <span
                        class="min-w-0 flex-1 text-sm"
                        :class="
                            step.done
                                ? 'text-muted-foreground line-through'
                                : 'font-medium'
                        "
                    >
                        {{ step.label }}
                    </span>

                    <Link
                        :href="step.href"
                        class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-primary"
                    >
                        {{ step.cta }}
                        <ArrowLeft class="size-3.5" />
                    </Link>
                </li>
            </ol>
        </div>
    </section>
</template>
