<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import JobPostingController from '@/actions/App/Http/Controllers/JobPostingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

const mode = ref<'paste' | 'link'>('paste');
const modes = [
    { value: 'paste', label: 'Paste text' },
    { value: 'link', label: 'Use a link' },
] as const;
</script>

<template>
    <Form
        v-bind="JobPostingController.store.form()"
        class="space-y-3"
        #default="{ errors, processing }"
    >
        <div class="inline-flex rounded-md border p-0.5 text-sm" role="tablist">
            <button
                v-for="option in modes"
                :key="option.value"
                type="button"
                role="tab"
                :aria-selected="mode === option.value"
                class="rounded px-3 py-1"
                :class="
                    mode === option.value
                        ? 'bg-muted font-medium'
                        : 'text-muted-foreground'
                "
                @click="mode = option.value"
            >
                {{ option.label }}
            </button>
        </div>

        <div v-if="mode === 'link'" class="grid gap-1">
            <Label for="source_url">Link to the vacancy</Label>
            <Input id="source_url" name="source_url" type="url" required />
            <p class="text-xs text-muted-foreground">
                We read the page for the title, company and description. If a
                site only loads in a browser, paste the text instead.
            </p>
            <InputError :message="errors.source_url" />
        </div>

        <div v-if="mode === 'paste'" class="space-y-3">
            <div class="grid gap-1">
                <Label for="description">Vacancy text</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="8"
                    placeholder="Paste the whole vacancy here"
                    required
                />
                <InputError :message="errors.description" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label for="title"
                        >Title
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="title"
                        name="title"
                        placeholder="Read from the text if empty"
                    />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-1">
                    <Label for="company"
                        >Company
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input id="company" name="company" />
                    <InputError :message="errors.company" />
                </div>
            </div>
            <div class="grid gap-1">
                <Label for="paste_source_url"
                    >Link
                    <span class="text-muted-foreground">(optional)</span></Label
                >
                <Input id="paste_source_url" name="source_url" type="url" />
            </div>
        </div>

        <Button type="submit" :disabled="processing">
            <Spinner v-if="processing" />
            {{ processing ? 'Reading the vacancy…' : 'Add job' }}
        </Button>
    </Form>
</template>
