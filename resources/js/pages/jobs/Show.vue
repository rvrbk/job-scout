<script setup lang="ts">
import { Form, Head, Link } from "@inertiajs/vue3";
import { ExternalLink, Trash2 } from "@lucide/vue";
import GeneratedDocumentController from "@/actions/App/Http/Controllers/GeneratedDocumentController";
import JobPostingController from "@/actions/App/Http/Controllers/JobPostingController";
import DocumentStatusBadge from "@/components/DocumentStatusBadge.vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { show as showDocument } from "@/routes/documents";
import { index } from "@/routes/jobs";
import type { DocumentSummary, JobPosting, Option } from "@/types";

const props = defineProps<{
    posting: JobPosting;
    documents: DocumentSummary[];
    documentTypes: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: "Jobs", href: index() }],
    },
});

const confirmDelete = () =>
    window.confirm("Remove this job and every draft made for it?");
</script>

<template>
    <Head :title="posting.title" />

    <div class="grid flex-1 gap-8 p-4 lg:grid-cols-[1.4fr_1fr]">
        <section class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    :title="posting.title"
                    :description="posting.company ?? undefined"
                />
                <div class="flex items-center gap-1">
                    <a
                        v-if="posting.source_url"
                        :href="posting.source_url"
                        target="_blank"
                        rel="noopener"
                        class="p-2 text-muted-foreground hover:text-foreground"
                        title="Open posting"
                        ><ExternalLink class="size-4"
                    /></a>
                    <Link
                        :href="JobPostingController.destroy(posting.id)"
                        as="button"
                        class="p-2 text-muted-foreground hover:text-destructive"
                        title="Remove job"
                        :on-before="confirmDelete"
                        ><Trash2 class="size-4"
                    /></Link>
                </div>
            </div>
            <div
                class="rounded-xl border p-4 text-sm leading-relaxed whitespace-pre-line"
            >
                {{ posting.description }}
            </div>
        </section>

        <section class="space-y-6">
            <div class="space-y-3">
                <Heading
                    variant="small"
                    title="Draft a document"
                    description="Written only from confirmed facts in your ledger, then checked sentence by sentence."
                />
                <Form
                    v-for="documentType in props.documentTypes"
                    :key="documentType.value"
                    v-bind="GeneratedDocumentController.store.form(posting.id)"
                    class="inline-block pr-2"
                    v-slot="{ errors, processing }"
                >
                    <input
                        type="hidden"
                        name="type"
                        :value="documentType.value"
                    />
                    <Button type="submit" :disabled="processing">
                        Draft {{ documentType.label }}
                    </Button>
                    <InputError :message="errors.type" class="mt-2" />
                </Form>
            </div>

            <div class="space-y-3">
                <Heading variant="small" title="Drafts" />
                <p
                    v-if="documents.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nothing drafted yet.
                </p>
                <ul class="divide-y rounded-xl border">
                    <li v-for="document in documents" :key="document.id">
                        <Link
                            :href="showDocument(document.id)"
                            class="flex items-center justify-between gap-4 p-3 hover:bg-muted/50"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ document.type_label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ document.created_at }}
                                </p>
                            </div>
                            <DocumentStatusBadge :status="document.status" />
                        </Link>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
