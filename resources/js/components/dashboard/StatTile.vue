<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    note?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1 rounded-xl border bg-card p-5">
        <span class="text-sm text-muted-foreground">{{ label }}</span>
        <span class="font-display text-4xl leading-none font-bold">{{
            value
        }}</span>
        <span v-if="note" class="mt-1 truncate text-xs text-muted-foreground">{{
            note
        }}</span>
    </div>
</template>
