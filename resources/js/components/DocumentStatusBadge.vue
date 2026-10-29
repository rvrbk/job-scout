<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { DocumentStatus } from '@/types';

defineProps<{ status: DocumentStatus }>();

const labels: Record<DocumentStatus, string> = {
    generating: 'Drafting…',
    verifying: 'Checking claims…',
    needs_review: 'Needs review',
    verified: 'All claims verified',
    approved: 'Approved',
    failed: 'Failed',
};

const variants: Record<
    DocumentStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    generating: 'outline',
    verifying: 'outline',
    needs_review: 'destructive',
    verified: 'secondary',
    approved: 'default',
    failed: 'destructive',
};
</script>

<template>
    <Badge :variant="variants[status]">{{ labels[status] }}</Badge>
</template>
