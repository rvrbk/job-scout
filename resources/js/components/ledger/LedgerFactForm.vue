<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import LedgerFactController from '@/actions/App/Http/Controllers/LedgerFactController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { LedgerFact } from '@/types';

const props = defineProps<{
    entryId: number;
    fact?: LedgerFact;
}>();

const emit = defineEmits<{ done: [] }>();

const formAction = () =>
    props.fact
        ? LedgerFactController.update.form(props.fact.id)
        : LedgerFactController.store.form(props.entryId);
</script>

<template>
    <Form
        v-bind="formAction()"
        :options="{ preserveScroll: true }"
        :reset-on-success="!fact"
        class="space-y-3 rounded-lg border border-dashed p-3"
        @success="emit('done')"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label :for="`statement-${fact?.id ?? `new-${entryId}`}`"
                >Fact</Label
            >
            <Textarea
                :id="`statement-${fact?.id ?? `new-${entryId}`}`"
                name="statement"
                required
                rows="2"
                :default-value="fact?.statement ?? ''"
                placeholder="Cut checkout API response time by 40% by adding query caching."
            />
            <p class="text-xs text-muted-foreground">
                One specific, checkable statement. Include numbers only if you
                can back them up.
            </p>
            <InputError :message="errors.statement" />
        </div>

        <div class="grid gap-2">
            <Input
                name="evidence_url"
                type="url"
                :default-value="fact?.evidence_url ?? ''"
                placeholder="Evidence link (optional)"
            />
            <InputError :message="errors.evidence_url" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <Label class="flex items-center gap-3 text-sm">
                <Checkbox
                    name="confirmed"
                    :default-value="fact?.confirmed ?? false"
                />
                <span>I confirm this is true</span>
            </Label>
            <div class="flex gap-2">
                <Button
                    v-if="fact"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="emit('done')"
                    >Cancel</Button
                >
                <Button type="submit" size="sm" :disabled="processing">
                    {{ fact ? 'Save fact' : 'Add fact' }}
                </Button>
            </div>
        </div>
    </Form>
</template>
