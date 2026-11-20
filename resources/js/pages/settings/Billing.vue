<script setup lang="ts">
import { Form, Head, Link, usePoll } from "@inertiajs/vue3";
import { watch } from "vue";
import Heading from "@/components/Heading.vue";
import { Button } from "@/components/ui/button";
import { checkout, index, portal } from "@/routes/billing";
import { index as ledgerIndex } from "@/routes/ledger";

defineOptions({
    layout: {
        breadcrumbs: [{ title: "Billing", href: index() }],
    },
});

const props = defineProps<{
    enforced: boolean;
    exempt: boolean;
    subscribed: boolean;
    status: string | null;
    cancels: boolean;
    endsAt: string | null;
    hasCustomer: boolean;
    justPaid: boolean;
}>();

// Right after paying, the subscription arrives by webhook a moment later: keep checking until it does.
const { start, stop } = usePoll(
    2000,
    { only: ["subscribed", "status", "endsAt", "cancels"] },
    { autoStart: false },
);

watch(
    () => props.justPaid && !props.subscribed,
    (waiting) => (waiting ? start() : stop()),
    { immediate: true },
);

const date = (value: string) => new Date(value).toLocaleDateString();
</script>

<template>
    <Head title="Billing" />

    <h1 class="sr-only">Billing</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Billing"
            description="Job search is free. A subscription unlocks document generation, board suggestions, and fuller, twice-daily searches."
        />

        <p v-if="!enforced" class="text-sm text-muted-foreground">
            Subscriptions are not switched on yet, so everything is available.
        </p>

        <p
            v-else-if="exempt && !subscribed"
            class="text-sm text-muted-foreground"
        >
            Your account has free access. No subscription needed.
        </p>

        <template v-else-if="subscribed">
            <div
                v-if="justPaid"
                class="space-y-3 rounded-lg border border-primary/40 p-4 text-sm"
            >
                <p class="font-medium">Thanks, you are all set!</p>
                <p class="text-muted-foreground">
                    The first thing to do is fill your facts ledger: it is what
                    your job matches and documents are built from. Upload your
                    CV or write a few lines about yourself, confirm what is
                    true, and your first job search starts by itself.
                </p>
                <Button as-child>
                    <Link :href="ledgerIndex()">Fill your ledger</Link>
                </Button>
            </div>
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
                updates by itself once it is.
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
