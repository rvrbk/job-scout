<script setup lang="ts">
import { Form, Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { Bookmark, ExternalLink, Search, X } from '@lucide/vue';
import { computed, watch } from 'vue';
import JobSearchController from '@/actions/App/Http/Controllers/JobSearchController';
import Heading from '@/components/Heading.vue';
import CountryFlags from '@/components/jobs/CountryFlags.vue';
import DocumentBadges from '@/components/jobs/DocumentBadges.vue';
import JobSearchPreferencesForm from '@/components/jobs/JobSearchPreferencesForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index, show } from '@/routes/jobs';
import type { JobPostingSummary, JobSearchPreferences } from '@/types';

const props = defineProps<{
    postings: JobPostingSummary[];
    preferences: JobSearchPreferences;
    searching: boolean;
}>();

const page = usePage();
const canSearch = computed(() => page.props.auth.canSearch);

// A search runs in the background after the click, so keep checking until it is done.
const { start, stop } = usePoll(
    3000,
    { only: ['postings', 'preferences', 'searching'] },
    { autoStart: false },
);

watch(
    () => props.searching,
    (searching) => (searching ? start() : stop()),
    { immediate: true },
);

const lastSearchedAt = computed(() =>
    props.preferences.last_searched_at
        ? new Date(props.preferences.last_searched_at).toLocaleString()
        : null,
);

const found = computed(() => props.postings.filter((posting) => posting.found));

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Jobs', href: index() }],
    },
});
</script>

<template>
    <Head title="Jobs" />

    <div class="flex flex-col gap-8">
        <section class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    title="Best matches"
                    :description="
                        lastSearchedAt
                            ? `Jobs scored against your confirmed ledger. Last searched ${lastSearchedAt}.`
                            : 'Jobs scored against your confirmed ledger. New jobs are searched for every day.'
                    "
                />
                <Form
                    v-if="canSearch"
                    v-bind="JobSearchController.search.form()"
                    #default="{ processing }"
                >
                    <Button type="submit" :disabled="processing || searching">
                        <Spinner v-if="processing || searching" />
                        <Search v-else />
                        {{
                            processing || searching
                                ? 'Searching…'
                                : 'Search now'
                        }}
                    </Button>
                    <p
                        v-if="processing || searching"
                        class="mt-2 text-xs text-muted-foreground"
                        role="status"
                    >
                        Checking sources and scoring jobs. This can take a
                        few minutes. New matches appear here by themselves.
                    </p>
                </Form>
            </div>

            <p
                v-if="found.length === 0"
                class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                No matches yet. Your first search starts as soon as you confirm
                facts in your ledger. After that the daily search checks every
                source, scores new jobs against your ledger, and keeps those at or
                above {{ preferences.min_score }}.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <li
                    v-for="posting in found"
                    :key="posting.id"
                    class="flex items-start gap-4 border-l-2 p-4"
                    :class="
                        posting.is_new
                            ? 'border-l-primary bg-primary/5'
                            : 'border-l-transparent'
                    "
                >
                    <Badge
                        :variant="
                            (posting.match_score ?? 0) >= 80
                                ? 'default'
                                : 'secondary'
                        "
                        class="mt-0.5 w-12 justify-center"
                        >{{ posting.match_score }}</Badge
                    >
                    <CountryFlags :countries="posting.countries" class="mt-1" />
                    <Link :href="show(posting.id)" class="min-w-0 flex-1">
                        <p
                            :class="
                                posting.is_new ? 'font-semibold' : 'font-medium'
                            "
                        >
                            {{ posting.title }}
                            <Badge
                                v-if="posting.is_new"
                                class="ml-2 align-middle"
                                >New</Badge
                            >
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                [
                                    posting.company,
                                    posting.location,
                                    posting.remote ? 'Remote' : null,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                        <p v-if="posting.match_reason" class="mt-1 text-sm">
                            {{ posting.match_reason }}
                        </p>
                        <p
                            v-if="posting.found_by"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Found by the
                            {{
                                posting.found_by.trigger === 'scheduled'
                                    ? 'scheduled'
                                    : 'manual'
                            }}
                            search on
                            {{ new Date(posting.found_by.at).toLocaleString() }}
                            <template v-if="posting.source">
                                · via {{ posting.source }}</template
                            >
                        </p>
                        <DocumentBadges :documents="posting.documents" />
                    </Link>
                    <div class="flex items-center gap-1">
                        <a
                            v-if="posting.source_url"
                            :href="posting.source_url"
                            target="_blank"
                            rel="noopener"
                            class="p-2 text-muted-foreground hover:text-foreground"
                            title="Open original posting"
                            ><ExternalLink class="size-4"
                        /></a>
                        <Link
                            :href="JobSearchController.save(posting.id)"
                            as="button"
                            preserve-scroll
                            class="p-2 text-muted-foreground hover:text-foreground"
                            title="Save job"
                            ><Bookmark class="size-4"
                        /></Link>
                        <Link
                            :href="JobSearchController.dismiss(posting.id)"
                            as="button"
                            preserve-scroll
                            class="p-2 text-muted-foreground hover:text-destructive"
                            title="Not interested"
                            ><X class="size-4"
                        /></Link>
                    </div>
                </li>
            </ul>

            <details class="rounded-xl border p-4">
                <summary class="cursor-pointer text-sm font-medium">
                    Search preferences
                </summary>

                <JobSearchPreferencesForm :preferences="preferences" />
            </details>
        </section>
    </div>
</template>
