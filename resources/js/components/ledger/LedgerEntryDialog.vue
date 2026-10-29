<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import LedgerEntryController from '@/actions/App/Http/Controllers/LedgerEntryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { LedgerEntry, Option } from '@/types';

const props = defineProps<{
    types: Option[];
    entry?: LedgerEntry;
}>();

const open = ref(false);

const formAction = () =>
    props.entry
        ? LedgerEntryController.update.form(props.entry.id)
        : LedgerEntryController.store.form();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <Form
                v-bind="formAction()"
                :options="{ preserveScroll: true }"
                :reset-on-success="!entry"
                class="space-y-4"
                @success="open = false"
                v-slot="{ errors, processing }"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        entry ? 'Edit entry' : 'Add entry'
                    }}</DialogTitle>
                    <DialogDescription>
                        A role, project, degree or certificate. Titles,
                        employers and dates are copied into your CV exactly as
                        written here.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="type">Type</Label>
                    <select
                        id="type"
                        name="type"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
                        :value="entry?.type ?? types[0]?.value"
                    >
                        <option
                            v-for="type in types"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <div class="grid gap-2">
                    <Label for="title">Title</Label>
                    <Input
                        id="title"
                        name="title"
                        required
                        :default-value="entry?.title"
                        placeholder="Backend Developer"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="organization">Organisation</Label>
                        <Input
                            id="organization"
                            name="organization"
                            :default-value="entry?.organization ?? ''"
                        />
                        <InputError :message="errors.organization" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="location">Location</Label>
                        <Input
                            id="location"
                            name="location"
                            :default-value="entry?.location ?? ''"
                        />
                        <InputError :message="errors.location" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="started_on">Started</Label>
                        <Input
                            id="started_on"
                            name="started_on"
                            type="month"
                            :default-value="entry?.started_on ?? ''"
                        />
                        <InputError :message="errors.started_on" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="ended_on">Ended (empty if current)</Label>
                        <Input
                            id="ended_on"
                            name="ended_on"
                            type="month"
                            :default-value="entry?.ended_on ?? ''"
                        />
                        <InputError :message="errors.ended_on" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="skills">Skills (comma separated)</Label>
                    <Input
                        id="skills"
                        name="skills"
                        :default-value="entry?.skills.join(', ') ?? ''"
                        placeholder="PHP, Laravel, PostgreSQL"
                    />
                    <InputError :message="errors.skills" />
                </div>

                <div class="grid gap-2">
                    <Label for="evidence_url">Evidence link</Label>
                    <Input
                        id="evidence_url"
                        name="evidence_url"
                        type="url"
                        :default-value="entry?.evidence_url ?? ''"
                    />
                    <InputError :message="errors.evidence_url" />
                </div>

                <Label class="flex items-center gap-3">
                    <Checkbox
                        name="confirmed"
                        :default-value="entry?.confirmed ?? false"
                    />
                    <span>I confirm this entry is accurate</span>
                </Label>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing">Save</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
