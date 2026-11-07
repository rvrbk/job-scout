<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import GeneratedDocumentController from '@/actions/App/Http/Controllers/GeneratedDocumentController';
import DocumentStatusBadge from '@/components/DocumentStatusBadge.vue';
import DraftDocumentForm from '@/components/documents/DraftDocumentForm.vue';
import Heading from '@/components/Heading.vue';
import { show as showDocument } from '@/routes/documents';
import { documents } from '@/routes/jobs';
import type {
    DocumentSummary,
    Option,
    StyleOption,
    ToneOption,
} from '@/types';

defineProps<{
    generalDocuments: DocumentSummary[];
    documentTypes: Option[];
    documentStyles: StyleOption[];
    documentTones: ToneOption[];
    preferredStyle: string | null;
    preferredTone: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Jobs', href: documents() }],
    },
});
</script>

<template>
    <Head title="Documents without a job" />

    <section class="max-w-2xl space-y-4">
        <Heading
            title="Without a job"
            description="A general CV or cover letter written from your ledger alone, to adapt later."
        />
        <DraftDocumentForm
            :action="GeneratedDocumentController.storeGeneral.form()"
            :document-types="documentTypes"
            :document-styles="documentStyles"
            :document-tones="documentTones"
            :preferred-style="preferredStyle"
            :preferred-tone="preferredTone"
        />
        <ul v-if="generalDocuments.length" class="divide-y rounded-xl border">
            <li v-for="document in generalDocuments" :key="document.id">
                <Link
                    :href="showDocument(document.id)"
                    class="flex items-center justify-between gap-4 p-3 hover:bg-muted/50"
                >
                    <div>
                        <p class="font-medium">
                            General {{ document.type_label }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ document.created_at }}
                        </p>
                    </div>
                    <DocumentStatusBadge :status="document.status" />
                </Link>
            </li>
        </ul>
    </section>
</template>
