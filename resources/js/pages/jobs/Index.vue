<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Bookmark, ExternalLink, Search, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import GeneratedDocumentController from '@/actions/App/Http/Controllers/GeneratedDocumentController';
import JobSearchController from '@/actions/App/Http/Controllers/JobSearchController';
import DocumentStatusBadge from '@/components/DocumentStatusBadge.vue';
import DraftDocumentForm from '@/components/documents/DraftDocumentForm.vue';
import Heading from '@/components/Heading.vue';
import AddJobForm from '@/components/jobs/AddJobForm.vue';
import DocumentBadges from '@/components/jobs/DocumentBadges.vue';
import JobSearchPreferencesForm from '@/components/jobs/JobSearchPreferencesForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { show as showDocument } from '@/routes/documents';
import { index, show } from '@/routes/jobs';
import type {
    DocumentSummary,
    JobPostingSummary,
    JobSearchPreferences,
    Option,
    Paginated,
    StyleOption,
    ToneOption,
} from '@/types';

const props = defineProps<{
    postings: JobPostingSummary[];
    saved: Paginated<JobPostingSummary>;
    filters: { q: string; sort: 'newest' | 'oldest' };
    preferences: JobSearchPreferences;
    generalDocuments: DocumentSummary[];
    documentTypes: Option[];
    documentStyles: StyleOption[];
    documentTones: ToneOption[];
    preferredStyle: string | null;
    preferredTone: string | null;
}>();

const lastSearchedAt = computed(() =>
    props.preferences.last_searched_at
        ? new Date(props.preferences.last_searched_at).toLocaleString()
        : null,
);

const found = computed(() => props.postings.filter((posting) => posting.found));

const search = ref(props.filters.q);
const sort = ref(props.filters.sort);
let timer: ReturnType<typeof setTimeout> | undefined;

// Search and sort run on the server so they cover every page, not just this one.
const reloadSaved = () =>
    router.get(
        index.url(),
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
        breadcrumbs: [{ title: 'Jobs', href: index() }],
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
                        lastSearchedAt
                            ? `Jobs scored against your confirmed ledger. Last searched ${lastSearchedAt}.`
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
                        {{ processing ? 'Searching…' : 'Search now' }}
                    </Button>
                    <p
                        v-if="processing"
                        class="mt-2 text-xs text-muted-foreground"
                        role="status"
                    >
                        Checking sources and scoring jobs. This can take a
                        minute.
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

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <Heading title="Saved jobs" />
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

        <div class="grid gap-8 lg:grid-cols-2">
            <section class="space-y-4">
                <Heading
                    title="Add a job"
                    description="Paste a vacancy or link to it. Then draft a CV or cover letter for it."
                />
                <AddJobForm />
            </section>

            <section class="space-y-4">
                <Heading
                    title="Without a job"
                    description="A general CV or cover letter written from your ledger alone, to adapt later."
                />
                <DraftDocumentForm
                    :action="GeneratedDocumentController.storeGeneral.form()"
                    :document-types="documentTypes"
                    :document-styles="documentStyles"
                    :document-tones="documentTones"
                    :preferred-style="preferredStyle"
                    :preferred-tone="preferredTone"
                />
                <ul
                    v-if="generalDocuments.length"
                    class="divide-y rounded-xl border"
                >
                    <li v-for="document in generalDocuments" :key="document.id">
                        <Link
                            :href="showDocument(document.id)"
                            class="flex items-center justify-between gap-4 p-3 hover:bg-muted/50"
                        >
                            <div>
                                <p class="font-medium">
                                    General {{ document.type_label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ document.created_at }}
                                </p>
                            </div>
                            <DocumentStatusBadge :status="document.status" />
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
