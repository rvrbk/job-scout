<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { login } from '@/routes';

type Step = {
    title: string;
    description: string;
};

const steps: Step[] = [
    {
        title: 'Write down what you have done',
        description:
            'Type it the way you would tell a friend, or upload your existing CV. Job Scout sorts it into roles, projects, skills and single, checkable facts.',
    },
    {
        title: 'Confirm what is true',
        description:
            'Nothing is trusted until you say so. Every fact starts as not confirmed, and only confirmed facts can ever end up in an application.',
    },
    {
        title: 'Find jobs that fit',
        description:
            'Job Scout searches your sources and scores each posting against your ledger, with the reason for the score and the gaps it sees.',
    },
    {
        title: 'Bring your own vacancy',
        description:
            'Found a job somewhere else? Paste the vacancy or link to it and Job Scout reads out the title, company and requirements. Saved jobs are searchable and sorted by date.',
    },
    {
        title: 'Draft with receipts',
        description:
            'A CV or cover letter is written from your confirmed facts. Every sentence is checked against the facts it cites, and anything unsupported is flagged for you.',
    },
    {
        title: 'Make it sound like you',
        description:
            'Pick a PDF style and a tone, or give a sample of your own writing so the draft matches your voice. No vacancy yet? Draft a general CV or cover letter from your ledger alone.',
    },
    {
        title: 'Review, approve, download',
        description:
            'You approve the draft once it checks out, then export it as a document. Nothing is sent anywhere on your behalf.',
    },
];

const open = ref(false);
const current = ref(0);

const step = computed(() => steps[current.value]);
const isFirst = computed(() => current.value === 0);
const isLast = computed(() => current.value === steps.length - 1);

const go = (delta: number) => {
    current.value = Math.min(
        steps.length - 1,
        Math.max(0, current.value + delta),
    );
};

watch(open, (isOpen) => {
    if (isOpen) {
        current.value = 0;
    }
});

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowRight') {
        go(1);
    } else if (event.key === 'ArrowLeft') {
        go(-1);
    }
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-xl" @keydown="onKeydown">
            <div class="space-y-1">
                <p class="text-xs font-medium text-muted-foreground">
                    Step {{ current + 1 }} of {{ steps.length }}
                </p>
                <DialogTitle>{{ step.title }}</DialogTitle>
                <DialogDescription>{{ step.description }}</DialogDescription>
            </div>

            <div
                class="min-h-44 space-y-2 rounded-xl border bg-muted/30 p-4 text-sm"
                aria-label="Example"
            >
                <p class="text-xs text-muted-foreground">Example</p>

                <template v-if="current === 0">
                    <p class="rounded-lg border bg-background p-3 italic">
                        “At my last job I moved our reporting to Postgres and
                        mentored two juniors.”
                    </p>
                    <p class="text-xs text-muted-foreground">becomes</p>
                    <div class="rounded-lg border bg-background p-3">
                        <p class="font-semibold">Backend Developer</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            <li>Moved reporting to Postgres.</li>
                            <li>Mentored two junior developers.</li>
                        </ul>
                    </div>
                </template>

                <template v-else-if="current === 1">
                    <div
                        class="flex items-center justify-between gap-3 rounded-lg border bg-background p-3"
                    >
                        <span>Moved reporting to Postgres.</span>
                        <Badge variant="destructive">Not confirmed</Badge>
                    </div>
                    <div
                        class="flex items-center justify-between gap-3 rounded-lg border bg-background p-3"
                    >
                        <span>Mentored two junior developers.</span>
                        <Badge variant="secondary"
                            ><Check class="size-3" /> Confirmed</Badge
                        >
                    </div>
                </template>

                <template v-else-if="current === 2">
                    <div class="rounded-lg border bg-background p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold">Senior Backend Engineer</p>
                            <Badge>86% match</Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Remote · found by your last search
                        </p>
                        <p class="mt-2">
                            Strong overlap on Postgres and mentoring. Gap: no
                            confirmed Kubernetes experience.
                        </p>
                    </div>
                </template>

                <template v-else-if="current === 3">
                    <div
                        class="flex items-start gap-2 rounded-lg border bg-background p-3"
                    >
                        <Check class="mt-0.5 size-4 shrink-0 text-primary" />
                        <p>
                            Moved reporting to Postgres.
                            <span class="block text-xs text-muted-foreground"
                                >Supported by 1 confirmed fact</span
                            >
                        </p>
                    </div>
                    <div
                        class="flex items-start gap-2 rounded-lg border border-destructive/50 bg-background p-3"
                    >
                        <X class="mt-0.5 size-4 shrink-0 text-destructive" />
                        <p>
                            Led a team of ten engineers.
                            <span class="block text-xs text-destructive"
                                >Unsupported: no fact says this</span
                            >
                        </p>
                    </div>
                </template>

                <template v-else>
                    <div
                        class="flex items-center justify-between gap-3 rounded-lg border bg-background p-3"
                    >
                        <div>
                            <p class="font-semibold">
                                CV · Senior Backend Engineer
                            </p>
                            <p class="text-xs text-muted-foreground">
                                All claims supported
                            </p>
                        </div>
                        <Badge variant="secondary">Approved</Badge>
                    </div>
                </template>
            </div>

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
                    <Button v-if="!isFirst" variant="outline" @click="go(-1)">
                        <ArrowLeft /> Back
                    </Button>
                    <Button v-if="!isLast" @click="go(1)">
                        Next <ArrowRight />
                    </Button>
                    <Button v-else as-child>
                        <Link :href="login()">Log in to start</Link>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
