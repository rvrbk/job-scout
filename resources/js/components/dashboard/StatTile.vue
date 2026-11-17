<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import type { InertiaLinkProps } from "@inertiajs/vue3";

defineProps<{
    label: string;
    value: string | number;
    note?: string;
    href?: NonNullable<InertiaLinkProps["href"]>;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="flex flex-col gap-1 rounded-xl border bg-card p-5"
        :class="href && 'transition-colors hover:bg-accent'"
    >
        <span class="text-sm text-muted-foreground">{{ label }}</span>
        <span class="font-display text-4xl leading-none font-bold">{{
            value
        }}</span>
        <span v-if="note" class="mt-1 truncate text-xs text-muted-foreground">{{
            note
        }}</span>
    </component>
</template>
