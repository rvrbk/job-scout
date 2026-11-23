<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PlatformCredit from '@/components/PlatformCredit.vue';
import { Button } from '@/components/ui/button';
import { home, login, register } from '@/routes';

defineProps<{
    page: {
        slug: string;
        title: string;
        heading: string;
        description: string;
        intro: string;
        numbered: boolean;
        sections: { heading: string; body: string }[];
    };
}>();

const links = [
    { href: '/how-it-works', label: 'How it works' },
    { href: '/features', label: 'Features' },
    { href: '/faq', label: 'FAQ' },
];
</script>

<template>
    <Head :title="page.title" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="mx-auto flex w-full max-w-5xl items-center justify-between p-6">
            <Link :href="home()" class="flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <AppLogoIcon class="size-5" />
                </div>
                <span class="font-display text-lg font-bold tracking-tight">Job Scout</span>
            </Link>
            <nav class="flex items-center gap-2">
                <Button as-child variant="ghost">
                    <Link :href="login()">Log in</Link>
                </Button>
                <Button as-child>
                    <Link :href="register()">Sign up</Link>
                </Button>
            </nav>
        </header>

        <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-8 px-6 py-12">
            <div class="space-y-3">
                <h1 class="text-4xl leading-tight font-bold tracking-tight">{{ page.heading }}</h1>
                <p class="text-lg text-muted-foreground">{{ page.intro }}</p>
            </div>

            <component :is="page.numbered ? 'ol' : 'ul'" class="space-y-4">
                <li v-for="(section, index) in page.sections" :key="section.heading" class="space-y-2 rounded-xl border bg-card p-5">
                    <h2 class="text-lg font-bold">
                        <span v-if="page.numbered" class="mr-2 text-primary">{{ index + 1 }}.</span>{{ section.heading }}
                    </h2>
                    <p class="text-muted-foreground">{{ section.body }}</p>
                </li>
            </component>

            <section class="flex flex-col items-start gap-4 rounded-2xl border bg-card p-8">
                <h2 class="text-2xl font-bold tracking-tight">Start with the facts, finish with a document you can stand behind.</h2>
                <Button as-child size="lg">
                    <Link :href="register()">Sign up</Link>
                </Button>
            </section>
        </main>

        <footer class="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-4 p-6">
            <nav class="flex gap-4 text-sm text-muted-foreground">
                <Link v-for="link in links" :key="link.href" :href="link.href" class="hover:text-foreground">{{ link.label }}</Link>
            </nav>
            <PlatformCredit />
        </footer>
    </div>
</template>
