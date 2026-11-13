<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
      علامة الشركة: صندوق بضاعة مجسّم — ثلاث أوجه بدرجات شفافية مختلفة.
      بتتقري صح من 16px لحد 64px وبتاخد لون الحاوية عن طريق currentColor.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <!-- الوجه العلوي -->
        <path fill="currentColor" d="M12 1.6 21.4 7 12 12.4 2.6 7z" />
        <!-- الوجه الجانبي الأيمن (أفتح) -->
        <path
            fill="currentColor"
            fill-opacity=".55"
            d="M22.2 8.3v7.4L12.8 21v-7.4z"
        />
        <!-- الوجه الجانبي الأيسر -->
        <path
            fill="currentColor"
            fill-opacity=".8"
            d="M1.8 8.3 11.2 13.6V21L1.8 15.7z"
        />
    </svg>
</template>
