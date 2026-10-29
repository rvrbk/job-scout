<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import LedgerEntryController from '@/actions/App/Http/Controllers/LedgerEntryController';
import LedgerFactController from '@/actions/App/Http/Controllers/LedgerFactController';
import Heading from '@/components/Heading.vue';
import LedgerEntryDialog from '@/components/ledger/LedgerEntryDialog.vue';
import LedgerFactForm from '@/components/ledger/LedgerFactForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/ledger';
import type { LedgerEntry, Option } from '@/types';

const props = defineProps<{
    entries: LedgerEntry[];
    types: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Facts ledger', href: index() }],
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

        <p class="text-sm text-muted-foreground">
            {{ confirmedFactCount }} confirmed
            {{ confirmedFactCount === 1 ? 'fact' : 'facts' }} available for
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
                                .join(' · ')
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
