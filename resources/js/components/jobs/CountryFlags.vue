<script setup lang="ts">
defineProps<{
    countries: { code: string; name: string }[];
}>();
</script>

<template>
    <div
        v-if="countries.length"
        class="flex shrink-0 flex-col items-center gap-1 text-sm leading-none"
    >
        <span
            v-for="country in countries"
            :key="country.code"
            :class="`fi fis fi-${country.code.toLowerCase()} rounded-sm`"
            :title="country.name"
            role="img"
            :aria-label="country.name"
        ></span>
    </div>
</template>
