<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { checkout, index, portal } from '@/routes/billing';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Billing', href: index() }],
    },
});

defineProps<{
    enforced: boolean;
    exempt: boolean;
    subscribed: boolean;
    status: string | null;
    cancels: boolean;
    endsAt: string | null;
    hasCustomer: boolean;
    justPaid: boolean;
}>();

const date = (value: string) => new Date(value).toLocaleDateString();
</script>

<template>
    <Head title="Billing" />

    <h1 class="sr-only">Billing</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Billing"
            description="A subscription unlocks document generation and job search."
        />

        <p v-if="!enforced" class="text-sm text-muted-foreground">
            Subscriptions are not switched on yet, so everything is available.
        </p>

        <p v-else-if="exempt && !subscribed" class="text-sm text-muted-foreground">
            Your account has free access. No subscription needed.
        </p>

        <template v-else-if="subscribed">
            <div class="rounded-lg border p-4 text-sm">
                <p class="font-medium">Your subscription is active.</p>
                <p v-if="endsAt" class="text-muted-foreground">
                    {{
                        cancels
                            ? `It ends on ${date(endsAt)} and will not renew.`
                            : `Renews on ${date(endsAt)}.`
                    }}
                </p>
            </div>
            <Form v-bind="portal.form()" v-slot="{ processing }">
                <Button variant="outline" :disabled="processing">
                    Manage subscription
                </Button>
            </Form>
        </template>

        <template v-else>
            <p
                v-if="justPaid"
                class="rounded-lg border p-4 text-sm text-muted-foreground"
            >
                Thanks! Waiting for the payment to be confirmed. This page
                updates once it is; reload in a few seconds if it does not.
            </p>
            <p v-else class="text-sm text-muted-foreground">
                You do not have an active subscription<template v-if="status">
                    (status: {{ status }})</template
                >.
            </p>
            <div class="flex gap-2">
                <Form v-bind="checkout.form()" v-slot="{ processing }">
                    <Button :disabled="processing">Subscribe</Button>
                </Form>
                <Form
                    v-if="hasCustomer"
                    v-bind="portal.form()"
                    v-slot="{ processing }"
                >
                    <Button variant="outline" :disabled="processing">
                        Manage subscription
                    </Button>
                </Form>
            </div>
        </template>
    </div>
</template>
