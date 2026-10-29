<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import JobPostingController from '@/actions/App/Http/Controllers/JobPostingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index, show } from '@/routes/jobs';
import type { JobPostingSummary } from '@/types';

defineProps<{
    postings: JobPostingSummary[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Jobs', href: index() }],
    },
});
</script>

<template>
    <Head title="Jobs" />

    <div class="grid flex-1 gap-8 p-4 lg:grid-cols-[1fr_1.2fr]">
        <section class="space-y-4">
            <Heading
                title="Add a job"
                description="Paste a posting you want to apply for. Automatic job search comes in a later step."
            />

            <Form
                v-bind="JobPostingController.store.form()"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Job title</Label>
                    <Input id="title" name="title" required />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="company">Company</Label>
                        <Input id="company" name="company" />
                        <InputError :message="errors.company" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="source_url">Link</Label>
                        <Input id="source_url" name="source_url" type="url" />
                        <InputError :message="errors.source_url" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="description">Job description</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows="12"
                        required
                    />
                    <InputError :message="errors.description" />
                </div>
                <Button type="submit" :disabled="processing">Save job</Button>
            </Form>
        </section>

        <section class="space-y-4">
            <Heading title="Saved jobs" />
            <p
                v-if="postings.length === 0"
                class="text-sm text-muted-foreground"
            >
                No jobs yet.
            </p>
            <ul class="divide-y rounded-xl border">
                <li v-for="posting in postings" :key="posting.id">
                    <Link
                        :href="show(posting.id)"
                        class="flex items-center justify-between gap-4 p-4 hover:bg-muted/50"
                    >
                        <div>
                            <p class="font-medium">{{ posting.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ posting.company ?? 'Unknown company' }} Â·
                                added {{ posting.created_at }}
                            </p>
                        </div>
                        <span class="text-sm text-muted-foreground"
                            >{{ posting.documents_count }}
                            {{
                                posting.documents_count === 1
                                    ? 'draft'
                                    : 'drafts'
                            }}</span
                        >
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
