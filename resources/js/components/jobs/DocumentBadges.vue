<script setup lang="ts">
import { Check, Loader, TriangleAlert } from "@lucide/vue";
import { Badge } from "@/components/ui/badge";
import type { JobPostingSummary } from "@/types";

defineProps<{
    documents: JobPostingSummary["documents"];
}>();

const statusLabels: Record<string, string> = {
    generating: "generating",
    verifying: "verifying",
    needs_review: "needs review",
    verified: "verified",
    approved: "approved",
    failed: "failed",
};
</script>

<template>
    <div v-if="documents.length" class="mt-2 flex flex-wrap gap-1.5">
        <Badge
            v-for="document in documents"
            :key="`${document.type}-${document.status}`"
            :variant="document.status === 'failed' ? 'destructive' : 'outline'"
            :title="`${document.type_label}: ${statusLabels[document.status]}`"
        >
            <Loader
                v-if="
                    document.status === 'generating' ||
                    document.status === 'verifying'
                "
                class="animate-spin"
            />
            <TriangleAlert
                v-else-if="
                    document.status === 'failed' ||
                    document.status === 'needs_review'
                "
            />
            <Check v-else />
            {{ document.type_label }}
        </Badge>
    </div>
</template>
