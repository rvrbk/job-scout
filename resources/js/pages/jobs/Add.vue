<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AddJobForm from '@/components/jobs/AddJobForm.vue';
import { create } from '@/routes/jobs';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Jobs', href: create() }],
    },
});
</script>

<template>
    <Head title="Import a vacancy" />

    <section class="max-w-2xl space-y-4">
        <Heading
            title="Import a vacancy"
            description="Paste a vacancy or link to it. Then draft a CV or cover letter for it."
        />
        <AddJobForm />
    </section>
</template>
