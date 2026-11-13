<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Hammer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    title: string;
    description: string;
    breadcrumbs: BreadcrumbItem[];
}>();

defineOptions({
    layout: (props: { breadcrumbs: BreadcrumbItem[] }) => ({
        breadcrumbs: props.breadcrumbs,
    }),
});
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 items-center justify-center p-6">
        <div
            class="w-full max-w-md rounded-xl border border-border bg-card p-10 text-center shadow-soft"
        >
            <div
                class="mx-auto flex size-14 items-center justify-center rounded-full bg-accent text-accent-foreground"
            >
                <Hammer class="size-6" />
            </div>

            <h1 class="mt-5 text-xl font-bold tracking-tight">{{ title }}</h1>

            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                {{ description }}
            </p>

            <div
                class="mt-5 inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground"
            >
                <span class="size-1.5 rounded-full bg-primary" />
                قيد التطوير — قريبًا
            </div>

            <!-- على لوحة التحكم نفسها مفيش معنى لزرار الرجوع -->
            <Button
                v-if="breadcrumbs.length > 1"
                as-child
                variant="outline"
                class="mt-7 w-full"
            >
                <Link href="/dashboard">
                    <ArrowRight class="size-4" />
                    الرجوع للوحة التحكم
                </Link>
            </Button>
        </div>
    </div>
</template>
