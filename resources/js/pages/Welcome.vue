<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const points = [
    {
        title: 'Facts, not fluff',
        description:
            'Build a ledger of what you have really done. Only the facts you confirm can end up in an application.',
    },
    {
        title: 'Jobs that fit you',
        description:
            'Job Scout searches several sources and scores every posting against your ledger, with the reason and the gaps.',
    },
    {
        title: 'Drafts you can trust',
        description:
            'Every sentence of a CV or cover letter is checked against the facts it cites before you can download it.',
    },
];
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
                        <Link :href="register()">Get started</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-14 px-6 py-12"
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
                <div v-if="!$page.props.auth.user" class="flex gap-3 pt-2">
                    <Button as-child size="lg">
                        <Link :href="register()">Create your ledger</Link>
                    </Button>
                    <Button as-child size="lg" variant="outline">
                        <Link :href="login()">Log in</Link>
                    </Button>
                </div>
            </section>

            <ul class="grid gap-4 md:grid-cols-3">
                <li
                    v-for="point in points"
                    :key="point.title"
                    class="space-y-2 rounded-xl border bg-card p-5"
                >
                    <h2 class="text-lg font-bold">{{ point.title }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ point.description }}
                    </p>
                </li>
            </ul>
        </main>
    </div>
</template>
