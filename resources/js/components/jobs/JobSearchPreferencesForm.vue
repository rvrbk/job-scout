<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { Plus, X } from "@lucide/vue";
import { ref } from "vue";
import JobSearchController from "@/actions/App/Http/Controllers/JobSearchController";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import type { JobSearchPreferences } from "@/types";

const props = defineProps<{
    preferences: JobSearchPreferences;
}>();

const modes = [
    { value: "any", label: "On-site, hybrid or remote" },
    { value: "hybrid", label: "Hybrid or remote" },
    { value: "remote_only", label: "Remote only" },
];

const selectClass =
    "h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30";

let nextKey = 0;
const locations = ref(
    props.preferences.locations.map((location) => ({
        key: nextKey++,
        ...location,
    })),
);

const addLocation = () =>
    locations.value.push({ key: nextKey++, place: "", mode: "hybrid" });

const lines = (items: string[]) => items.join("\n");
</script>

<template>
    <Form
        v-bind="JobSearchController.update.form()"
        class="mt-4 space-y-4"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2 sm:col-span-2">
                <Label>Locations</Label>
                <div
                    v-for="(row, index) in locations"
                    :key="row.key"
                    class="flex flex-wrap items-center gap-2"
                >
                    <Input
                        :name="`locations[${index}][place]`"
                        v-model="row.place"
                        placeholder="Netherlands, Europe, Utrecht…"
                        class="min-w-48 flex-1 sm:max-w-xs"
                    />
                    <select
                        :name="`locations[${index}][mode]`"
                        v-model="row.mode"
                        :class="selectClass"
                    >
                        <option
                            v-for="mode in modes"
                            :key="mode.value"
                            :value="mode.value"
                        >
                            {{ mode.label }}
                        </option>
                    </select>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        title="Remove location"
                        @click="locations.splice(index, 1)"
                    >
                        <X class="size-4" />
                    </Button>
                </div>
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="locations.length >= 10"
                        @click="addLocation"
                    >
                        <Plus /> Add location
                    </Button>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <Label for="remote_preference" class="w-28"
                        >Everywhere else</Label
                    >
                    <select
                        id="remote_preference"
                        name="remote_preference"
                        :class="selectClass"
                        :value="preferences.remote_preference"
                    >
                        <option
                            v-for="mode in modes"
                            :key="mode.value"
                            :value="mode.value"
                        >
                            {{ mode.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-muted-foreground">
                    Each place can be a country, a region like Europe or EMEA,
                    or a city, and has its own setting for the jobs you accept
                    there. Places not listed use "Everywhere else". Remote jobs
                    are accepted from anywhere.
                </p>
                <InputError
                    :message="errors.locations || errors.remote_preference"
                />
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
                <div class="flex flex-wrap items-center gap-2">
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
                    <select
                        name="salary_currency"
                        aria-label="Currency"
                        :class="selectClass"
                        :value="preferences.salary_currency"
                    >
                        <option
                            v-for="currency in preferences.currencies"
                            :key="currency"
                            :value="currency"
                        >
                            {{ currency }}
                        </option>
                    </select>
                </div>
                <InputError
                    :message="
                        errors.salary_min ||
                        errors.salary_max ||
                        errors.salary_currency
                    "
                />
            </div>
            <Button type="submit" :disabled="processing"
                >Save preferences</Button
            >
        </div>
    </Form>
</template>
