<script setup lang="ts">
defineProps<{
    countries: { code: string; name: string }[];
}>();

// A country code is two letters; the matching regional-indicator pair renders as that country's flag.
const flag = (code: string) =>
    String.fromCodePoint(
        ...[...code.toUpperCase()].map((c) => 0x1f1a5 + c.charCodeAt(0)),
    );
</script>

<template>
    <div
        v-if="countries.length"
        class="flex shrink-0 flex-col items-center gap-0.5 text-sm leading-none"
    >
        <span
            v-for="country in countries"
            :key="country.code"
            :title="country.name"
            role="img"
            :aria-label="country.name"
            >{{ flag(country.code) }}</span
        >
    </div>
</template>
