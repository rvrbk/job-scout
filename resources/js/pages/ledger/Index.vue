<script setup lang="ts">
import { Form, Head, Link } from "@inertiajs/vue3";
import { ExternalLink, Pencil, Plus, Trash2, Upload } from "@lucide/vue";
import { computed, ref } from "vue";
import LedgerEntryController from "@/actions/App/Http/Controllers/LedgerEntryController";
import LedgerImportController from "@/actions/App/Http/Controllers/LedgerImportController";
import LedgerFactController from "@/actions/App/Http/Controllers/LedgerFactController";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import LedgerEntryDialog from "@/components/ledger/LedgerEntryDialog.vue";
import LedgerFactForm from "@/components/ledger/LedgerFactForm.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { index } from "@/routes/ledger";
import type { LedgerEntry, LedgerImport, Option } from "@/types";

const props = defineProps<{
    entries: LedgerEntry[];
    types: Option[];
    import: LedgerImport;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: "Facts ledger", href: index() }],
    },
});

const editingFactId = ref<number | null>(null);
const addingFactTo = ref<number | null>(null);

const confirmDelete = (message: string) => window.confirm(message);

const typeLabel = (value: string) =>
    props.types.find((type) => type.value === value)?.label ?? value;

const confirmedFactCount = computed(
    () =>
        props.entries
            .filter((entry) => entry.confirmed)
            .flatMap((entry) => entry.facts)
            .filter((fact) => fact.confirmed).length,
);
</script>

<template>
    <Head title="Facts ledger" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Facts ledger"
                description="Everything a generated CV or cover letter may say about you. Only confirmed entries and confirmed facts are used."
            />
            <LedgerEntryDialog :types="types">
                <Button><Plus /> Add entry</Button>
            </LedgerEntryDialog>
        </div>

        <section class="space-y-2 rounded-xl border p-4">
            <h3 class="text-sm font-medium">Start from an existing CV</h3>
            <p class="text-xs text-muted-foreground">
                Upload a PDF, Word or text file. Its jobs, education and skills
                are added to the ledger next to what you already have, and
                nothing existing is changed. Everything imported starts as not
                confirmed, so check it before it can be used in a document.
            </p>
            <Form
                v-bind="LedgerImportController.store.form()"
                class="flex flex-wrap items-center gap-2"
                reset-on-success
                #default="{ errors, processing }"
            >
                <input
                    type="file"
                    name="cv"
                    accept=".pdf,.docx,.txt,.md"
                    required
                    :disabled="props.import.running"
                    class="text-sm file:mr-3 file:rounded-md file:border file:bg-transparent file:px-3 file:py-1.5 file:text-sm"
                />
                <Button
                    type="submit"
                    :disabled="processing || props.import.running"
                >
                    <Upload />
                    {{ props.import.running ? "Importing…" : "Import CV" }}
                </Button>
                <InputError :message="errors.cv" class="w-full" />
            </Form>
            <p
                v-if="props.import.running"
                class="text-xs text-muted-foreground"
                role="status"
            >
                Reading your CV. This can take a minute; reload the page to see
                the result.
            </p>
            <p
                v-else-if="props.import.result?.error"
                class="text-sm text-destructive"
            >
                {{ props.import.result.error }}
            </p>
            <p v-else-if="props.import.result" class="text-sm">
                Added {{ props.import.result.entries }}
                {{ props.import.result.entries === 1 ? "entry" : "entries" }}
                and {{ props.import.result.facts }}
                {{ props.import.result.facts === 1 ? "fact" : "facts" }}, all
                waiting for your confirmation.
            </p>
        </section>

        <p class="text-sm text-muted-foreground">
            {{ confirmedFactCount }} confirmed
            {{ confirmedFactCount === 1 ? "fact" : "facts" }} available for
            generation.
        </p>

        <div
            v-if="entries.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Your ledger is empty. Start with your current or most recent role,
            then add specific facts about what you did there.
        </div>

        <article
            v-for="entry in entries"
            :key="entry.id"
            class="space-y-4 rounded-xl border p-4"
        >
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-semibold">{{ entry.title }}</h3>
                        <Badge variant="outline">{{
                            typeLabel(entry.type)
                        }}</Badge>
                        <Badge v-if="entry.confirmed" variant="secondary"
                            >Confirmed</Badge
                        >
                        <Badge v-else variant="destructive"
                            >Not confirmed</Badge
                        >
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{
                            [entry.organization, entry.location, entry.period]
                                .filter(Boolean)
                                .join(" · ")
                        }}
                    </p>
                    <div
                        v-if="entry.skills.length"
                        class="flex flex-wrap gap-1 pt-1"
                    >
                        <Badge
                            v-for="skill in entry.skills"
                            :key="skill"
                            variant="outline"
                            >{{ skill }}</Badge
                        >
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <a
                        v-if="entry.evidence_url"
                        :href="entry.evidence_url"
                        target="_blank"
                        rel="noopener"
                        class="p-2 text-muted-foreground hover:text-foreground"
                        title="Evidence"
                        ><ExternalLink class="size-4"
                    /></a>
                    <LedgerEntryDialog :types="types" :entry="entry">
                        <Button variant="ghost" size="icon" title="Edit entry"
                            ><Pencil
                        /></Button>
                    </LedgerEntryDialog>
                    <Link
                        :href="LedgerEntryController.destroy(entry.id)"
                        as="button"
                        preserve-scroll
                        class="p-2 text-muted-foreground hover:text-destructive"
                        title="Delete entry"
                        :on-before="
                            () =>
                                confirmDelete(
                                    'Delete this entry and all of its facts?',
                                )
                        "
                        ><Trash2 class="size-4"
                    /></Link>
                </div>
            </header>

            <ul class="space-y-2">
                <li v-for="fact in entry.facts" :key="fact.id">
                    <LedgerFactForm
                        v-if="editingFactId === fact.id"
                        :entry-id="entry.id"
                        :fact="fact"
                        @done="editingFactId = null"
                    />
                    <div
                        v-else
                        class="group flex items-start justify-between gap-3 rounded-lg bg-muted/40 px-3 py-2 text-sm"
                    >
                        <div class="space-y-1">
                            <p>{{ fact.statement }}</p>
                            <div class="flex items-center gap-2">
                                <Badge
                                    v-if="!fact.confirmed"
                                    variant="destructive"
                                    >Not confirmed</Badge
                                >
                                <a
                                    v-if="fact.evidence_url"
                                    :href="fact.evidence_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-xs text-muted-foreground underline"
                                    >evidence</a
                                >
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                title="Edit fact"
                                @click="editingFactId = fact.id"
                                ><Pencil
                            /></Button>
                            <Link
                                :href="LedgerFactController.destroy(fact.id)"
                                as="button"
                                preserve-scroll
                                class="p-2 text-muted-foreground hover:text-destructive"
                                title="Delete fact"
                                :on-before="
                                    () => confirmDelete('Delete this fact?')
                                "
                                ><Trash2 class="size-4"
                            /></Link>
                        </div>
                    </div>
                </li>
            </ul>

            <LedgerFactForm
                v-if="addingFactTo === entry.id"
                :entry-id="entry.id"
                @done="addingFactTo = null"
            />
            <Button
                v-else
                variant="outline"
                size="sm"
                @click="addingFactTo = entry.id"
                ><Plus /> Add fact</Button
            >
        </article>
    </div>
</template>
