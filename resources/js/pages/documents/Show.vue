<script setup lang="ts">
import { Form, Head, Link, usePoll } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import GeneratedDocumentController from '@/actions/App/Http/Controllers/GeneratedDocumentController';
import ClaimRow from '@/components/documents/ClaimRow.vue';
import DocumentStatusBadge from '@/components/DocumentStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { index as jobsIndex, show as showJob } from '@/routes/jobs';
import type { DocumentClaim, GeneratedDocument } from '@/types';

const props = defineProps<{
    document: GeneratedDocument;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Jobs', href: jobsIndex() }],
    },
});

const { start, stop } = usePoll(
    2000,
    { only: ['document'] },
    { autoStart: false },
);

watch(
    () => props.document.is_processing,
    (isProcessing) => (isProcessing ? start() : stop()),
    { immediate: true },
);

const showPreview = ref(false);

const bySection = (section: DocumentClaim['section']) =>
    props.document.claims.filter((claim) => claim.section === section);

const groupClaims = (claims: DocumentClaim[]) =>
    Object.values(
        claims.reduce<Record<number, DocumentClaim[]>>((groups, claim) => {
            (groups[claim.group] ??= []).push(claim);

            return groups;
        }, {}),
    );

const summary = computed(() => bySection('summary'));
const experience = computed(() => groupClaims(bySection('experience')));
const skills = computed(() => bySection('skills'));
const paragraphs = computed(() => groupClaims(bySection('letter')));

const flaggedCount = computed(
    () =>
        props.document.claims.filter((claim) => claim.status === 'unsupported')
            .length,
);

const locked = computed(() => props.document.is_processing);
</script>

<template>
    <Head :title="`${document.type_label} – ${document.job.title}`" />

    <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <Heading
                    :title="`${document.type_label} for ${document.job.title}`"
                    :description="document.job.company ?? undefined"
                />
                <div class="flex items-center gap-2">
                    <DocumentStatusBadge :status="document.status" />
                    <Link
                        :href="showJob(document.job.id)"
                        class="text-sm text-muted-foreground underline"
                        >Back to job</Link
                    >
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <template v-if="document.status === 'approved'">
                    <a
                        :href="
                            GeneratedDocumentController.exportPdf.url(
                                document.id,
                            )
                        "
                    >
                        <Button><Download /> Download PDF</Button>
                    </a>
                    <a
                        :href="
                            GeneratedDocumentController.export.url(document.id)
                        "
                    >
                        <Button variant="secondary"
                            ><Download /> Export .md</Button
                        >
                    </a>
                </template>
                <Form
                    v-else
                    v-bind="
                        GeneratedDocumentController.approve.form(document.id)
                    "
                    :options="{ preserveScroll: true }"
                    v-slot="{ errors, processing }"
                >
                    <Button
                        type="submit"
                        :disabled="!document.can_be_approved || processing"
                        >Approve</Button
                    >
                    <InputError :message="errors.approve" class="mt-1" />
                </Form>
            </div>
        </div>

        <div
            v-if="flaggedCount > 0 && !locked"
            class="rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-sm"
        >
            {{ flaggedCount }}
            {{ flaggedCount === 1 ? 'sentence is' : 'sentences are' }} not
            backed by your ledger. Rewrite or remove
            {{ flaggedCount === 1 ? 'it' : 'them' }}, or add the missing fact to
            your ledger and draft again. You can approve once everything is
            verified.
        </div>

        <div
            v-if="document.status === 'failed'"
            class="space-y-3 rounded-lg border border-destructive/40 p-4 text-sm"
        >
            <p class="font-medium">This draft couldn't be completed.</p>
            <p class="text-muted-foreground">{{ document.error }}</p>
            <Form
                v-bind="GeneratedDocumentController.retry.form(document.id)"
                v-slot="{ processing }"
            >
                <Button type="submit" size="sm" :disabled="processing"
                    >Try again</Button
                >
            </Form>
        </div>

        <div
            v-if="document.status === 'generating'"
            class="space-y-3"
            aria-label="Drafting"
        >
            <p class="text-sm text-muted-foreground">
                Drafting from your ledger. This usually takes under a minute.
            </p>
            <Skeleton class="h-6 w-2/3" />
            <Skeleton class="h-16 w-full" />
            <Skeleton class="h-16 w-full" />
            <Skeleton class="h-16 w-5/6" />
        </div>

        <template v-else>
            <section v-if="document.gaps.length" class="space-y-2">
                <Heading
                    variant="small"
                    title="Gaps"
                    description="What the job asks for that your ledger doesn't show. These are left out rather than padded."
                />
                <ul class="space-y-1 text-sm">
                    <li
                        v-for="gap in document.gaps"
                        :key="gap.requirement"
                        class="rounded-lg bg-muted/50 px-3 py-2"
                    >
                        <span class="font-medium">{{ gap.requirement }}</span>
                        <span class="text-muted-foreground">
                            — {{ gap.note }}</span
                        >
                    </li>
                </ul>
            </section>

            <template v-if="document.type === 'cv'">
                <section v-if="summary.length" class="space-y-2">
                    <Heading variant="small" title="Summary" />
                    <ClaimRow
                        v-for="claim in summary"
                        :key="claim.id"
                        :claim="claim"
                        :locked="locked"
                    />
                </section>

                <section v-if="experience.length" class="space-y-4">
                    <Heading variant="small" title="Experience" />
                    <div
                        v-for="(bullets, groupIndex) in experience"
                        :key="groupIndex"
                        class="space-y-2"
                    >
                        <p class="text-sm font-medium">
                            {{
                                bullets[0].entry_heading ??
                                'Entry no longer in your ledger'
                            }}
                        </p>
                        <ClaimRow
                            v-for="claim in bullets"
                            :key="claim.id"
                            :claim="claim"
                            :locked="locked"
                        />
                    </div>
                </section>

                <section v-if="skills.length" class="space-y-2">
                    <Heading variant="small" title="Skills" />
                    <ClaimRow
                        v-for="claim in skills"
                        :key="claim.id"
                        :claim="claim"
                        :locked="locked"
                    />
                </section>
            </template>

            <template v-else>
                <section
                    v-for="(sentences, groupIndex) in paragraphs"
                    :key="groupIndex"
                    class="space-y-2"
                >
                    <Heading
                        variant="small"
                        :title="`Paragraph ${groupIndex + 1}`"
                    />
                    <ClaimRow
                        v-for="claim in sentences"
                        :key="claim.id"
                        :claim="claim"
                        :locked="locked"
                    />
                </section>
            </template>

            <section v-if="document.markdown" class="space-y-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="showPreview = !showPreview"
                >
                    {{ showPreview ? 'Hide preview' : 'Show preview' }}
                </Button>
                <pre
                    v-if="showPreview"
                    class="overflow-x-auto rounded-lg border bg-muted/30 p-4 text-sm whitespace-pre-wrap"
                    >{{ document.markdown }}</pre>
            </section>
        </template>
    </div>
</template>
