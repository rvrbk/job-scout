<script setup lang="ts">
import { Form, Head, Link } from "@inertiajs/vue3";
import { ExternalLink, Search, X } from "@lucide/vue";
import { computed } from "vue";
import JobSearchController from "@/actions/App/Http/Controllers/JobSearchController";
import Heading from "@/components/Heading.vue";
import DocumentBadges from "@/components/jobs/DocumentBadges.vue";
import JobSearchPreferencesForm from "@/components/jobs/JobSearchPreferencesForm.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { index, show } from "@/routes/jobs";
import type { JobPostingSummary, JobSearchPreferences } from "@/types";

const props = defineProps<{
    postings: JobPostingSummary[];
    preferences: JobSearchPreferences;
}>();

const found = computed(() => props.postings.filter((posting) => posting.found));
const saved = computed(() =>
    props.postings.filter((posting) => !posting.found),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: "Jobs", href: index() }],
    },
});
</script>

<template>
    <Head title="Jobs" />

    <div class="flex flex-1 flex-col gap-8 p-4">
        <section class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    title="Matches"
                    :description="
                        preferences.last_searched_at
                            ? `Jobs scored against your confirmed ledger. Last searched ${preferences.last_searched_at}.`
                            : 'Jobs scored against your confirmed ledger. Run a search to find some.'
                    "
                />
                <Form
                    v-bind="JobSearchController.search.form()"
                    #default="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        <Search v-else />
                        {{ processing ? "Searching…" : "Search now" }}
                    </Button>
                    <p
                        v-if="processing"
                        class="mt-2 text-xs text-muted-foreground"
                        role="status"
                    >
                        Checking sources and scoring jobs. This can take a minute.
                    </p>
                </Form>
            </div>

            <p
                v-if="found.length === 0"
                class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                No matches yet. A search checks every source, scores new jobs
                against your ledger, and keeps those at or above
                {{ preferences.min_score }}.
            </p>

            <ul v-else class="divide-y rounded-xl border">
                <li
                    v-for="posting in found"
                    :key="posting.id"
                    class="flex items-start gap-4 p-4"
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
                    <Link :href="show(posting.id)" class="min-w-0 flex-1">
                        <p class="font-medium">{{ posting.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                [
                                    posting.company,
                                    posting.location,
                                    posting.remote ? "Remote" : null,
                                ]
                                    .filter(Boolean)
                                    .join(" · ")
                            }}
                        </p>
                        <p v-if="posting.match_reason" class="mt-1 text-sm">
                            {{ posting.match_reason }}
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

        <section class="space-y-4">
            <Heading title="Saved jobs" />
            <p v-if="saved.length === 0" class="text-sm text-muted-foreground">
                No jobs yet.
            </p>
            <ul class="divide-y rounded-xl border">
                <li v-for="posting in saved" :key="posting.id">
                    <Link
                        :href="show(posting.id)"
                        class="flex items-center justify-between gap-4 p-4 hover:bg-muted/50"
                    >
                        <div>
                            <p class="font-medium">{{ posting.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ posting.company ?? "Unknown company" }} ·
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
                </li>
            </ul>
        </section>
    </div>
</template>
