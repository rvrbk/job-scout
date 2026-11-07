<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { create, documents, index, saved } from '@/routes/jobs';
import type { NavItem } from '@/types';

const tabs: NavItem[] = [
    { title: 'Matches', href: index() },
    { title: 'Saved', href: saved() },
    { title: 'Add a job', href: create() },
    { title: 'Without a job', href: documents() },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Jobs"
            description="Find matches, keep the ones you like, and draft documents for them."
        />

        <nav
            class="-mt-2 flex gap-1 overflow-x-auto border-b"
            aria-label="Jobs sections"
        >
            <Link
                v-for="tab in tabs"
                :key="toUrl(tab.href)"
                :href="tab.href"
                class="-mb-px border-b-2 px-3 py-2 text-sm font-medium whitespace-nowrap"
                :class="
                    isCurrentUrl(tab.href)
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
                :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
                >{{ tab.title }}</Link
            >
        </nav>

        <slot />
    </div>
</template>
