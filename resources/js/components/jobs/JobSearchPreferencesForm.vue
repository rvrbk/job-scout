<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import JobSearchController from "@/actions/App/Http/Controllers/JobSearchController";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import type { JobSearchPreferences } from "@/types";

defineProps<{
    preferences: JobSearchPreferences;
}>();

const lines = (items: string[]) => items.join("\n");
</script>

<template>
    <Form
        v-bind="JobSearchController.update.form()"
        class="mt-4 space-y-4"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="locations">Locations</Label>
                <Textarea
                    id="locations"
                    name="locations"
                    rows="3"
                    placeholder="Heerenveen&#10;Netherlands"
                    :default-value="lines(preferences.locations)"
                />
                <p class="text-xs text-muted-foreground">
                    One place per line, e.g. Netherlands. Hybrid and on-site
                    jobs outside these places are skipped; remote jobs are never
                    filtered by location.
                </p>
                <InputError :message="errors.locations" />
            </div>
            <div class="grid gap-2">
                <Label for="remote_preference">Remote</Label>
                <select
                    id="remote_preference"
                    name="remote_preference"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
                    :value="preferences.remote_preference"
                >
                    <option value="any">Remote or on-site</option>
                    <option value="hybrid">Hybrid or remote</option>
                    <option value="remote_only">Remote only</option>
                </select>
                <InputError :message="errors.remote_preference" />
            </div>
            <div class="grid gap-2">
                <Label for="keywords">Keywords</Label>
                <Textarea
                    id="keywords"
                    name="keywords"
                    rows="3"
                    placeholder="laravel&#10;vue"
                    :default-value="lines(preferences.keywords)"
                />
                <p class="text-xs text-muted-foreground">
                    A job must mention at least one. Leave empty to consider
                    everything.
                </p>
                <InputError :message="errors.keywords" />
            </div>
            <div class="grid gap-2">
                <Label for="exclusions">Exclude</Label>
                <Textarea
                    id="exclusions"
                    name="exclusions"
                    rows="3"
                    placeholder="junior&#10;wordpress"
                    :default-value="lines(preferences.exclusions)"
                />
                <p class="text-xs text-muted-foreground">
                    Jobs mentioning any of these are skipped.
                </p>
                <InputError :message="errors.exclusions" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="feeds">Extra RSS feeds</Label>
            <Textarea
                id="feeds"
                name="feeds"
                rows="2"
                placeholder="One https feed URL per line"
                :default-value="lines(preferences.feeds)"
            />
            <p class="text-xs text-muted-foreground">
                Always searched: Arbeitnow, Remotive, Jobicy, Remote OK,
                Himalayas, The Muse and We Work Remotely. Jooble and Careerjet
                join in when their API keys are set on the server.
            </p>
            <InputError :message="errors.feeds" />
        </div>

        <div class="grid gap-2">
            <Label for="boards">Company job boards</Label>
            <Textarea
                id="boards"
                name="boards"
                rows="2"
                placeholder="greenhouse:stripe&#10;lever:palantir"
                :default-value="lines(preferences.boards)"
            />
            <p class="text-xs text-muted-foreground">
                One per line as provider:company, where provider is greenhouse,
                lever, ashby or workable and company is the name in the board's
                URL.
            </p>
            <InputError :message="errors.boards" />
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div class="grid gap-2">
                <Label for="min_score">Minimum match (0-100)</Label>
                <Input
                    id="min_score"
                    name="min_score"
                    type="number"
                    min="0"
                    max="100"
                    class="w-28"
                    :default-value="preferences.min_score"
                />
                <InputError :message="errors.min_score" />
            </div>
            <div class="grid gap-2">
                <Label for="salary_min"
                    >Salary expectation (gross per year)</Label
                >
                <div class="flex items-center gap-2">
                    <Input
                        id="salary_min"
                        name="salary_min"
                        type="number"
                        min="0"
                        step="1000"
                        placeholder="From"
                        class="w-32"
                        :default-value="preferences.salary_min ?? undefined"
                    />
                    <span class="text-sm text-muted-foreground">to</span>
                    <Input
                        id="salary_max"
                        name="salary_max"
                        type="number"
                        min="0"
                        step="1000"
                        placeholder="To"
                        class="w-32"
                        :default-value="preferences.salary_max ?? undefined"
                    />
                </div>
                <InputError :message="errors.salary_min || errors.salary_max" />
            </div>
            <Button type="submit" :disabled="processing"
                >Save preferences</Button
            >
        </div>
    </Form>
</template>
