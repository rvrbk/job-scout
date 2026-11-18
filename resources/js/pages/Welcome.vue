<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import LandingDemo from "@/components/LandingDemo.vue";
import LandingTour from "@/components/LandingTour.vue";
import AppLogoIcon from "@/components/AppLogoIcon.vue";
import PlatformCredit from "@/components/PlatformCredit.vue";
import { Button } from "@/components/ui/button";
import { dashboard, login, register } from "@/routes";

const points = [
    {
        title: "Facts, not fluff",
        description:
            "Build a ledger of what you have really done. Only the facts you confirm can end up in an application.",
    },
    {
        title: "Jobs that fit you",
        description:
            "Job Scout searches several sources and scores every posting against your ledger. Or paste or link any vacancy yourself.",
    },
    {
        title: "Drafts you can trust",
        description:
            "Every sentence of a CV or cover letter is checked against the facts it cites before you can download it. In your tone, for a job or in general.",
    },
];

defineProps<{
    steps: { title: string; description: string }[];
    faqs: { question: string; answer: string }[];
}>();
</script>

<template>
    <Head title="Applications that only say what you can back up" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between p-6"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="size-5" />
                </div>
                <span class="font-display text-lg font-bold tracking-tight"
                    >Job Scout</span
                >
            </div>
            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()">Dashboard</Link>
                </Button>
                <template v-else>
                    <Button as-child variant="ghost">
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Sign up</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-16 px-6 py-12"
        >
            <section class="max-w-2xl space-y-5">
                <h1
                    class="text-4xl leading-tight font-bold tracking-tight sm:text-5xl"
                >
                    Applications that only say what
                    <span class="text-primary">you can back up.</span>
                </h1>
                <p class="text-lg text-muted-foreground">
                    Find jobs that match your real experience, then draft a CV
                    and cover letter where every claim traces back to a fact you
                    confirmed.
                </p>
                <div
                    v-if="!$page.props.auth.user"
                    class="flex flex-wrap gap-3 pt-2"
                >
                    <Button as-child size="lg">
                        <Link :href="register()">Sign up</Link>
                    </Button>
                    <Button as-child size="lg" variant="outline">
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <LandingTour>
                        <Button size="lg" variant="outline">Take a tour</Button>
                    </LandingTour>
                </div>
            </section>

            <ul class="grid gap-4 md:grid-cols-3">
                <li
                    v-for="point in points"
                    :key="point.title"
                    class="reveal space-y-2 rounded-xl border bg-card p-5"
                    :style="{ '--i': points.indexOf(point) }"
                >
                    <h2 class="text-lg font-bold">{{ point.title }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ point.description }}
                    </p>
                </li>
            </ul>

            <section aria-labelledby="demo-heading" class="reveal space-y-5">
                <div class="max-w-2xl space-y-2">
                    <h2
                        id="demo-heading"
                        class="text-2xl font-bold tracking-tight sm:text-3xl"
                    >
                        See it work in under a minute
                    </h2>
                    <p class="text-muted-foreground">
                        A guided walkthrough of the whole flow, from a few
                        sentences about your work to a checked PDF.
                    </p>
                </div>
                <LandingDemo />
            </section>

            <section aria-labelledby="how-it-works" class="space-y-5">
                <h2
                    id="how-it-works"
                    class="text-2xl font-bold tracking-tight sm:text-3xl"
                >
                    How it works
                </h2>
                <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="reveal space-y-2 rounded-xl border bg-card p-5"
                        :style="{ '--i': index }"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                            >{{ index + 1 }}</span
                        >
                        <h3 class="font-bold">{{ step.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </li>
                </ol>
            </section>

            <section aria-labelledby="faq-heading" class="space-y-5">
                <h2
                    id="faq-heading"
                    class="text-2xl font-bold tracking-tight sm:text-3xl"
                >
                    Questions
                </h2>
                <div class="divide-y rounded-xl border bg-card">
                    <details
                        v-for="faq in faqs"
                        :key="faq.question"
                        class="group p-5"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold"
                        >
                            {{ faq.question }}
                            <span
                                class="text-muted-foreground transition-transform group-open:rotate-45"
                                aria-hidden="true"
                                >+</span
                            >
                        </summary>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ faq.answer }}
                        </p>
                    </details>
                </div>
            </section>

            <section
                v-if="!$page.props.auth.user"
                class="reveal flex flex-col items-start gap-4 rounded-2xl border bg-card p-8"
            >
                <h2 class="text-2xl font-bold tracking-tight">
                    Start with the facts, finish with a document you can stand
                    behind.
                </h2>
                <div class="flex flex-wrap gap-3">
                    <Button as-child size="lg">
                        <Link :href="register()">Sign up</Link>
                    </Button>
                    <LandingTour>
                        <Button size="lg" variant="outline">Take a tour</Button>
                    </LandingTour>
                </div>
            </section>
        </main>

        <footer class="mx-auto w-full max-w-5xl p-6">
            <PlatformCredit />
        </footer>
    </div>
</template>

<style scoped>
.reveal {
    animation: reveal 0.7s ease-out backwards;
    animation-delay: calc(var(--i, 0) * 90ms);
}

@supports (animation-timeline: view()) {
    .reveal {
        animation: reveal linear both;
        animation-timeline: view();
        animation-range: entry 0% entry 35%;
        animation-delay: 0s;
    }
}

@keyframes reveal {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .reveal {
        animation: none;
    }
}
</style>
