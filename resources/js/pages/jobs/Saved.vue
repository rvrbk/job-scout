<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bookmark } from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import JobSearchController from '@/actions/App/Http/Controllers/JobSearchController';
import Heading from '@/components/Heading.vue';
import DocumentBadges from '@/components/jobs/DocumentBadges.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { saved as savedRoute, show } from '@/routes/jobs';
import type { JobPostingSummary, Paginated } from '@/types';

const props = defineProps<{
    saved: Paginated<JobPostingSummary>;
    filters: { q: string; sort: 'newest' | 'oldest' };
}>();

const search = ref(props.filters.q);
const sort = ref(props.filters.sort);
let timer: ReturnType<typeof setTimeout> | undefined;

// Search and sort run on the server so they cover every page, not just this one.
const reloadSaved = () =>
    router.get(
        savedRoute.url(),
        {
            q: search.value || undefined,
            sort: sort.value === 'newest' ? undefined : sort.value,
        },
        {
            only: ['saved', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );

const onSearch = () => {
    clearTimeout(timer);
    timer = setTimeout(reloadSaved, 300);
};

onBeforeUnmount(() => clearTimeout(timer));

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Jobs', href: savedRoute() }],
    },
});
</script>

<template>
    <Head title="Saved jobs" />

    <div class="flex flex-col gap-8">
        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <Heading
                    title="Saved jobs"
                    description="Jobs you bookmarked or added yourself."
                />
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search title, company or location"
                        aria-label="Search saved jobs"
                        class="w-64"
                        @input="onSearch"
                    />
                    <select
                        v-model="sort"
                        aria-label="Sort saved jobs"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
                        @change="reloadSaved"
                    >
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                    </select>
                </div>
            </div>
            <p
                v-if="saved.data.length === 0"
                class="text-sm text-muted-foreground"
            >
                {{
                    filters.q
                        ? `No saved jobs match "${filters.q}".`
                        : 'No saved jobs yet. Use the bookmark on a match to keep it here.'
                }}
            </p>
            <ul v-if="saved.data.length" class="divide-y rounded-xl border">
                <li
                    v-for="posting in saved.data"
                    :key="posting.id"
                    class="flex items-center"
                >
                    <Link
                        :href="show(posting.id)"
                        class="flex min-w-0 flex-1 items-center justify-between gap-4 p-4 hover:bg-muted/50"
                    >
                        <div>
                            <p class="font-medium">{{ posting.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ posting.company ?? 'Unknown company' }} ·
                                added {{ posting.created_at }}
                            </p>
                        </div>
                        <DocumentBadges
                            v-if="posting.documents.length"
                            :documents="posting.documents"
                        />
                        <span v-else class="text-sm text-muted-foreground"
                            >No drafts</span
                        >
                    </Link>
                    <Link
                        v-if="posting.saved"
                        :href="JobSearchController.save(posting.id)"
                        as="button"
                        preserve-scroll
                        class="p-2 pr-4 text-primary hover:text-muted-foreground"
                        title="Remove from saved"
                        ><Bookmark class="size-4 fill-current"
                    /></Link>
                </li>
            </ul>
            <nav
                v-if="saved.last_page > 1"
                class="flex items-center justify-between text-sm"
                aria-label="Saved jobs pages"
            >
                <p class="text-muted-foreground">
                    {{ saved.from }}–{{ saved.to }} of {{ saved.total }}
                </p>
                <div class="flex gap-2">
                    <Button
                        v-if="saved.prev_page_url"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <Link
                            :href="saved.prev_page_url"
                            preserve-scroll
                            :only="['saved', 'filters']"
                            >Previous</Link
                        >
                    </Button>
                    <Button
                        v-if="saved.next_page_url"
                        as-child
                        variant="outline"
                        size="sm"
                    >
                        <Link
                            :href="saved.next_page_url"
                            preserve-scroll
                            :only="['saved', 'filters']"
                            >Next</Link
                        >
                    </Button>
                </div>
            </nav>
        </section>
    </div>
</template>
