<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { destroy, index, store } from '@/routes/invitations';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Invitations',
                href: index(),
            },
        ],
    },
});

defineProps<{
    invitations: {
        id: number;
        url: string | null;
        expires_at: string;
        accepted_at: string | null;
        accepted_by: string | null;
        expired: boolean;
    }[];
}>();

const copiedId = ref<number | null>(null);

async function copy(id: number, url: string) {
    try {
        await navigator.clipboard.writeText(url);
        copiedId.value = id;
        setTimeout(() => (copiedId.value = null), 2000);
    } catch {
        // Clipboard unavailable; the link stays selectable in the field.
    }
}

const date = (value: string) => new Date(value).toLocaleDateString();
</script>

<template>
    <Head title="Invitations" />

    <h1 class="sr-only">Invitations</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Invitations"
            description="Job Scout is invitation-only. Create a link and send it to someone; it works once and expires after 7 days."
        />

        <Form v-bind="store.form()" v-slot="{ processing }">
            <Button :disabled="processing" data-test="create-invitation-button">
                Create invitation link
            </Button>
        </Form>

        <ul v-if="invitations.length" class="space-y-3">
            <li
                v-for="invitation in invitations"
                :key="invitation.id"
                class="space-y-2 rounded-lg border p-3 text-sm"
            >
                <template v-if="invitation.url">
                    <input
                        readonly
                        :value="invitation.url"
                        class="w-full rounded-md border bg-muted px-2 py-1 font-mono text-xs"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-muted-foreground">
                            Expires {{ date(invitation.expires_at) }}
                        </span>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="copy(invitation.id, invitation.url)"
                            >
                                {{ copiedId === invitation.id ? 'Copied' : 'Copy' }}
                            </Button>
                            <Form v-bind="destroy.form(invitation.id)">
                                <Button size="sm" variant="ghost" type="submit">
                                    Revoke
                                </Button>
                            </Form>
                        </div>
                    </div>
                </template>
                <p v-else-if="invitation.accepted_at" class="text-muted-foreground">
                    Used by {{ invitation.accepted_by ?? 'a deleted user' }} on
                    {{ date(invitation.accepted_at) }}
                </p>
                <div v-else class="flex items-center justify-between">
                    <span class="text-muted-foreground">
                        Expired {{ date(invitation.expires_at) }}
                    </span>
                    <Form v-bind="destroy.form(invitation.id)">
                        <Button size="sm" variant="ghost" type="submit">
                            Remove
                        </Button>
                    </Form>
                </div>
            </li>
        </ul>
    </div>
</template>
