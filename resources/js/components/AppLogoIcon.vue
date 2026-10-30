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
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <circle
            cx="20"
            cy="20"
            r="15.5"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
        />
        <path
            fill="currentColor"
            d="M27.6 12.4 23 23l-10.6 4.6L17 17l10.6-4.6Z"
        />
        <circle cx="20" cy="20" r="2" fill="var(--logo-hole, transparent)" />
    </svg>
</template>
