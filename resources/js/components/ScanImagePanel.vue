<script setup lang="ts">
import { FileText, RotateCw, ZoomIn, ZoomOut } from '@lucide/vue';
import { ref } from 'vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';

/**
 * صورة الفاتورة الأصلية جنب الفورم.
 *
 * صور العملاء بتتصوّر بالموبايل: مايلة، ومقلوبة ٩٠ درجة ساعات، وخط
 * اليد صغير. فاللوحة فيها تدوير وتكبير — من غيرهم المستخدم هيضطر
 * يفتح الصورة في تاب تاني وهو بيراجع، ويفقد مكانه في الفورم.
 */

const props = defineProps<{
    image: { previewUrl: string; name: string; isPdf: boolean } | null;
}>();

const rotation = ref(0);
const zoomed = ref(false);

function rotate() {
    rotation.value = (rotation.value + 90) % 360;
}
</script>

<template>
    <SectionCard title="صورة الفاتورة" class="lg:sticky lg:top-20">
        <template v-if="props.image" #actions>
            <Button
                v-if="!props.image.isPdf"
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                aria-label="تدوير الصورة"
                data-test="scan-image-rotate"
                @click="rotate"
            >
                <RotateCw class="size-4" />
            </Button>
            <Button
                v-if="!props.image.isPdf"
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :aria-label="zoomed ? 'تصغير' : 'تكبير'"
                data-test="scan-image-zoom"
                @click="zoomed = !zoomed"
            >
                <component :is="zoomed ? ZoomOut : ZoomIn" class="size-4" />
            </Button>
        </template>

        <p v-if="!props.image" class="text-sm text-muted-foreground">
            مفيش صورة مرفقة على المسودة دي.
        </p>

        <a
            v-else-if="props.image.isPdf"
            :href="props.image.previewUrl"
            target="_blank"
            rel="noopener"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-input px-4 py-10 text-sm text-muted-foreground transition-colors hover:border-primary/50 hover:bg-accent"
        >
            <FileText class="size-7" />
            <span class="font-medium text-foreground">
                {{ props.image.name }}
            </span>
            <span class="text-xs">افتح الـ PDF في تاب جديد</span>
        </a>

        <div
            v-else
            class="scrollbar-thin overflow-auto rounded-lg border border-border bg-muted/30"
            :class="zoomed ? 'max-h-[75vh]' : 'max-h-[55vh]'"
        >
            <img
                :src="props.image.previewUrl"
                :alt="props.image.name"
                class="mx-auto origin-center transition-transform duration-200"
                :class="zoomed ? 'max-w-none' : 'w-full'"
                :style="{
                    transform: `rotate(${rotation}deg)${zoomed ? ' scale(1.6)' : ''}`,
                }"
                data-test="scan-image"
            />
        </div>
    </SectionCard>
</template>
