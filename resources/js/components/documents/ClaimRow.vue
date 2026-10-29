<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    CircleAlert,
    CircleCheck,
    LoaderCircle,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import DocumentClaimController from '@/actions/App/Http/Controllers/DocumentClaimController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { DocumentClaim } from '@/types';

defineProps<{
    claim: DocumentClaim;
    locked: boolean;
}>();

const editing = ref(false);
</script>

<template>
    <div
        class="group rounded-lg border px-3 py-2 text-sm"
        :class="{
            'border-destructive/50 bg-destructive/5':
                claim.status === 'unsupported',
        }"
    >
        <Form
            v-if="editing"
            v-bind="DocumentClaimController.update.form(claim.id)"
            :options="{ preserveScroll: true }"
            class="space-y-2"
            @success="editing = false"
            v-slot="{ errors, processing }"
        >
            <Textarea
                name="text"
                rows="3"
                :default-value="claim.text"
                required
            />
            <InputError :message="errors.text" />
            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="editing = false"
                    >Cancel</Button
                >
                <Button type="submit" size="sm" :disabled="processing"
                    >Save and re-check</Button
                >
            </div>
        </Form>

        <div v-else class="flex items-start gap-3">
            <LoaderCircle
                v-if="claim.status === 'pending'"
                class="mt-0.5 size-4 shrink-0 animate-spin text-muted-foreground"
            />
            <CircleCheck
                v-else-if="claim.status === 'supported'"
                class="mt-0.5 size-4 shrink-0 text-green-600"
            />
            <CircleAlert
                v-else
                class="mt-0.5 size-4 shrink-0 text-destructive"
            />

            <div class="flex-1 space-y-1">
                <p>
                    {{ claim.text }}
                    <span
                        v-if="claim.kind === 'framing'"
                        class="ml-1 text-xs text-muted-foreground"
                        >(framing)</span
                    >
                </p>
                <p
                    v-if="claim.verifier_note"
                    class="text-xs"
                    :class="
                        claim.status === 'unsupported'
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{ claim.verifier_note }}
                </p>
                <details v-if="claim.facts.length" class="text-xs">
                    <summary
                        class="cursor-pointer text-muted-foreground select-none"
                    >
                        {{ claim.facts.length }} cited
                        {{ claim.facts.length === 1 ? 'fact' : 'facts' }}
                    </summary>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        <li v-for="fact in claim.facts" :key="fact.id">
                            {{ fact.statement }}
                        </li>
                    </ul>
                </details>
            </div>

            <div v-if="!locked" class="flex shrink-0 gap-1">
                <Button
                    v-if="claim.kind !== 'skill'"
                    variant="ghost"
                    size="icon"
                    title="Edit sentence"
                    @click="editing = true"
                    ><Pencil
                /></Button>
                <Link
                    :href="DocumentClaimController.destroy(claim.id)"
                    as="button"
                    preserve-scroll
                    class="p-2 text-muted-foreground hover:text-destructive"
                    title="Remove sentence"
                    ><Trash2 class="size-4"
                /></Link>
            </div>
        </div>
    </div>
</template>
