<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StackedBars from '@/components/dashboard/StackedBars.vue';
import StatTile from '@/components/dashboard/StatTile.vue';
import Heading from '@/components/Heading.vue';
import { dashboard } from '@/routes';
import { index as jobsIndex } from '@/routes/jobs';
import { index as ledgerIndex } from '@/routes/ledger';

type Split = { label: string; matches: number; below: number };

const props = defineProps<{
    ledger: {
        usable_facts: number;
        total_facts: number;
        skills: number;
        by_type: { label: string; usable: number; other: number }[];
    };
    jobs: {
        min_score: number;
        scored: number;
        matches: number;
        best: { score: number; title: string; company: string | null } | null;
        by_score: Split[];
        by_day: Split[];
        days: number;
    };
    documents: { total: number; approved: number; to_review: number };
    lastSearchedAt: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const matchSeries = (rows: Split[]) => [
    {
        label: `Match (${props.jobs.min_score}+)`,
        values: rows.map((row) => row.matches),
    },
    {
        label: 'Below your minimum',
        values: rows.map((row) => row.below),
    },
];

const ledgerSeries = computed(() => [
    { label: 'Usable', values: props.ledger.by_type.map((row) => row.usable) },
    {
        label: 'Not confirmed yet',
        values: props.ledger.by_type.map((row) => row.other),
    },
]);

const best = computed(() =>
    props.jobs.best
        ? `Best: ${props.jobs.best.score}, ${props.jobs.best.title}`
        : 'No matches yet',
);
const reviewNote = computed(() =>
    props.documents.to_review > 0
        ? `${props.documents.to_review} waiting for your review`
        : `of ${props.documents.total} ${props.documents.total === 1 ? 'draft' : 'drafts'}`,
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            :description="
                lastSearchedAt
                    ? `Last job search ${lastSearchedAt}.`
                    : 'Where your ledger, job matches and drafts stand.'
            "
        />

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatTile
                label="Usable facts"
                :value="ledger.usable_facts"
                :note="`of ${ledger.total_facts} in your ledger`"
            />
            <StatTile
                label="Skills"
                :value="ledger.skills"
                note="confirmed in your ledger"
            />
            <StatTile label="Job matches" :value="jobs.matches" :note="best" />
            <StatTile
                label="Approved documents"
                :value="documents.approved"
                :note="reviewNote"
            />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <section class="space-y-4 rounded-xl border bg-card p-5">
                <div>
                    <h3 class="font-bold">How well jobs match you</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ jobs.scored }} scored jobs by match score. Your
                        minimum is {{ jobs.min_score }}.
                    </p>
                </div>
                <StackedBars
                    v-if="jobs.scored > 0"
                    title="Jobs by match score"
                    :categories="jobs.by_score.map((row) => row.label)"
                    :series="matchSeries(jobs.by_score)"
                    :label-every="2"
                />
                <p v-else class="text-sm text-muted-foreground">
                    No jobs scored yet.
                    <Link :href="jobsIndex()" class="underline">
                        Run a search
                    </Link>
                </p>
            </section>

            <section class="space-y-4 rounded-xl border bg-card p-5">
                <div>
                    <h3 class="font-bold">Jobs found per day</h3>
                    <p class="text-sm text-muted-foreground">
                        The last {{ jobs.days }} days.
                    </p>
                </div>
                <StackedBars
                    v-if="jobs.scored > 0"
                    title="Jobs found per day"
                    :categories="jobs.by_day.map((row) => row.label)"
                    :series="matchSeries(jobs.by_day)"
                    :label-every="2"
                />
                <p v-else class="text-sm text-muted-foreground">
                    Nothing found yet.
                </p>
            </section>

            <section
                class="space-y-4 rounded-xl border bg-card p-5 md:col-span-2"
            >
                <div>
                    <h3 class="font-bold">What is in your ledger</h3>
                    <p class="text-sm text-muted-foreground">
                        Facts per kind of entry. Only usable facts can appear in
                        a CV or cover letter.
                    </p>
                </div>
                <StackedBars
                    v-if="ledger.by_type.length > 0"
                    title="Facts per entry type"
                    horizontal
                    :categories="ledger.by_type.map((row) => row.label)"
                    :series="ledgerSeries"
                />
                <p v-else class="text-sm text-muted-foreground">
                    Your ledger is empty.
                    <Link :href="ledgerIndex()" class="underline">
                        Add your first entry
                    </Link>
                </p>
            </section>
        </div>
    </div>
</template>
