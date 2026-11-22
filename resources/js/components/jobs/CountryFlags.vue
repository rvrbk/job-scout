<script setup lang="ts">
import { Globe } from '@lucide/vue';

defineProps<{
    countries: { code: string; name: string }[];
}>();
</script>

<template>
    <span class="inline-flex shrink-0 items-center gap-1 align-middle">
        <template v-if="countries.length">
            <span
                v-for="country in countries"
                :key="country.code"
                :class="`fi fis fi-${country.code.toLowerCase()} rounded-sm`"
                :title="country.name"
                role="img"
                :aria-label="country.name"
            ></span>
        </template>
        <Globe
            v-else
            class="size-3.5 text-muted-foreground"
            title="Global"
            aria-label="Global"
        />
    </span>
</template>
