<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as jobsIndex } from '@/routes/jobs';
import { index as ledgerIndex } from '@/routes/ledger';

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

const steps = [
    {
        title: 'Build your facts ledger',
        description:
            'Add roles, projects, degrees and certificates, then write specific facts about each one and confirm them. Only confirmed facts are ever used.',
        href: ledgerIndex(),
        action: 'Open ledger',
    },
    {
        title: 'Add a job',
        description:
            'Paste a posting you want to apply for. Automatic job search is the next step on the roadmap.',
        href: jobsIndex(),
        action: 'Open jobs',
    },
    {
        title: 'Draft, check and approve',
        description:
            'Each sentence cites the facts it relies on and is checked against them. Gaps are listed instead of padded, and nothing can be exported until you approve it.',
        href: jobsIndex(),
        action: 'See drafts',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            title="Job Scout"
            description="Applications that only say what you can back up."
        />

        <ol class="grid gap-4 md:grid-cols-3">
            <li
                v-for="(step, index) in steps"
                :key="step.title"
                class="flex flex-col gap-3 rounded-xl border p-4"
            >
                <span class="text-sm text-muted-foreground"
                    >Step {{ index + 1 }}</span
                >
                <h3 class="font-semibold">{{ step.title }}</h3>
                <p class="flex-1 text-sm text-muted-foreground">
                    {{ step.description }}
                </p>
                <Button as-child variant="outline" class="self-start">
                    <Link :href="step.href">{{ step.action }}</Link>
                </Button>
            </li>
        </ol>
    </div>
</template>
