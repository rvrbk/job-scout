<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, X } from '@lucide/vue';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTour } from '@/composables/useTour';
import { index as billing } from '@/routes/billing';
import {
    create as jobsCreate,
    documents as jobsDocuments,
    index as jobsIndex,
} from '@/routes/jobs';
import { index as ledgerIndex } from '@/routes/ledger';
import { complete } from '@/routes/onboarding';

type Step = {
    title: string;
    description: string;
    points?: string[];
    cta?: { label: string; href: string };
};

const page = usePage();

// A plain request, not an Inertia visit: a visit would cancel the navigation of a step's link.
const markSeen = () => {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);

    fetch(complete().url, {
        method: 'POST',
        keepalive: true,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
        },
    }).catch(() => {});
};

const { open, current, paused, pause, resume, finish } = useTour();

const steps = computed<Step[]>(() => [
    {
        title: `Welcome to ${page.props.name}`,
        description:
            'Job Scout helps you apply with claims you can back up. It works in four moves, and this tour walks through each one. It takes about a minute.',
        points: [
            'Write down your experience and confirm what is true',
            'Find jobs that fit, or bring your own vacancy',
            'Draft a CV or cover letter from your confirmed facts',
            'Review, approve and download',
        ],
    },
    {
        title: 'Build your facts ledger',
        description:
            'Your ledger is the source of truth. Type your experience the way you would tell a friend, or upload your CV, and it is sorted into roles, projects, skills and single, checkable facts.',
        points: [
            'Add entries by hand or import a CV',
            'Edit or delete anything the import got wrong',
        ],
        cta: { label: 'Open the ledger', href: ledgerIndex().url },
    },
    {
        title: 'Confirm what is true',
        description:
            'Nothing is trusted until you say so. Every fact starts as not confirmed, and only confirmed facts can ever end up in an application. Take your time here: a confident ledger is what makes the drafts good.',
        cta: { label: 'Confirm your facts', href: ledgerIndex().url },
    },
    {
        title: 'Find jobs that fit',
        description:
            'Tell Job Scout what you are looking for and it searches your job boards, then scores each posting against your ledger with the reason and the gaps.',
        points: [
            'Use "Suggested job boards" to find company boards that fit your profile',
            'Best matches show what is new since your last search',
            'Save what you like, dismiss what you do not',
        ],
        cta: { label: 'Go to jobs', href: jobsIndex().url },
    },
    {
        title: 'Bring your own vacancy',
        description:
            'Found a job somewhere else? Paste the vacancy or a link and Job Scout reads out the title, company and requirements, so you can draft against it like any other job.',
        cta: { label: 'Import a vacancy', href: jobsCreate().url },
    },
    {
        title: 'Draft with receipts',
        description:
            'A CV or cover letter is written from your confirmed facts. Every sentence is checked against the facts it cites, and anything unsupported is flagged so you can fix it or drop it.',
        points: [
            'Pick a PDF style and a tone, or give a writing sample so it sounds like you',
            'No vacancy yet? Draft a general CV or cover letter from your ledger alone',
        ],
        cta: { label: 'Draft general documents', href: jobsDocuments().url },
    },
    {
        title: 'Review, approve, download',
        description:
            'You read every claim, approve the draft once it checks out, then export it as a document or PDF. Nothing is ever sent anywhere on your behalf.',
    },
    {
        title: 'Your plan',
        description: page.props.billing.enforced
            ? 'Searching for jobs and drafting documents need a subscription. You can manage it, or open the payment portal, from Settings → Billing. Your ledger and saved jobs stay yours either way.'
            : 'Everything is available to you right now. You can find your account settings, security options and passkeys under Settings.',
        cta: page.props.billing.enforced
            ? { label: 'Open billing', href: billing().url }
            : undefined,
    },
]);

const step = computed(() => steps.value[current.value]);
const isFirst = computed(() => current.value === 0);
const isLast = computed(() => current.value === steps.value.length - 1);

const go = (delta: number) => {
    current.value = Math.min(
        steps.value.length - 1,
        Math.max(0, current.value + delta),
    );
};

// Opening, pausing and finishing all count as having seen the tour.
watch(open, (isOpen) => {
    if (isOpen) {
        markSeen();
    }
});

const onOpenChange = (value: boolean) => {
    if (value) {
        open.value = true;
    } else {
        // Escape or a click outside keeps the place; "Skip" and "Start" end the tour.
        pause();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowRight') {
        go(1);
    } else if (event.key === 'ArrowLeft') {
        go(-1);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-xl" @keydown="onKeydown">
            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">
                    Step {{ current + 1 }} of {{ steps.length }}
                </p>
                <DialogTitle>{{ step.title }}</DialogTitle>
                <DialogDescription>{{ step.description }}</DialogDescription>
            </div>

            <ul
                v-if="step.points"
                class="list-disc space-y-1.5 rounded-xl border bg-muted/30 py-4 pr-4 pl-9 text-sm"
            >
                <li v-for="point in step.points" :key="point">{{ point }}</li>
            </ul>

            <Button v-if="step.cta" as-child variant="outline" class="w-fit">
                <Link
                    :href="step.cta.href"
                    @click="pause(Math.min(current + 1, steps.length - 1))"
                >
                    {{ step.cta.label }} <ArrowRight />
                </Link>
            </Button>

            <div class="flex items-center justify-between gap-3">
                <div class="flex gap-1.5" aria-hidden="true">
                    <span
                        v-for="(_, index) in steps"
                        :key="index"
                        class="size-2 rounded-full"
                        :class="
                            index === current
                                ? 'bg-primary'
                                : 'bg-muted-foreground/30'
                        "
                    />
                </div>
                <div class="flex gap-2">
                    <Button v-if="isFirst" variant="ghost" @click="finish()">
                        Skip
                    </Button>
                    <Button v-else variant="outline" @click="go(-1)">
                        <ArrowLeft /> Back
                    </Button>
                    <Button v-if="!isLast" @click="go(1)">
                        Next <ArrowRight />
                    </Button>
                    <Button v-else @click="finish()">Start</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <div
        v-if="paused && !open"
        class="fixed right-4 bottom-4 z-50 flex items-center gap-1 rounded-full border bg-background p-1 shadow-lg"
    >
        <Button size="sm" class="rounded-full" @click="resume()">
            Continue the tour · step {{ current + 1 }} of {{ steps.length }}
        </Button>
        <Button
            size="icon"
            variant="ghost"
            class="size-8 rounded-full"
            aria-label="End the tour"
            @click="finish()"
        >
            <X />
        </Button>
    </div>
</template>
