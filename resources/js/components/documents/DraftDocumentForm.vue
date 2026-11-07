<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Option, StyleOption, ToneOption } from '@/types';

const props = defineProps<{
    // The Wayfinder form for the route that creates the document.
    action: {
        action: string;
        method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    };
    documentTypes: Option[];
    documentStyles: StyleOption[];
    documentTones: ToneOption[];
    preferredStyle: string | null;
    preferredTone: string | null;
}>();

const style = ref(
    props.preferredStyle ?? props.documentStyles[0]?.value ?? 'classic',
);
const tone = ref(
    props.preferredTone ?? props.documentTones[0]?.value ?? 'professional',
);
</script>

<template>
    <Form v-bind="action" class="space-y-3" v-slot="{ errors, processing }">
        <div class="grid gap-1">
            <Label for="style">PDF style</Label>
            <select
                id="style"
                name="style"
                v-model="style"
                class="h-9 w-fit rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
            >
                <option
                    v-for="option in documentStyles"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <p class="text-xs text-muted-foreground">
                {{ documentStyles.find((o) => o.value === style)?.description }}
            </p>
        </div>
        <div class="grid gap-1">
            <Label for="tone">Tone</Label>
            <select
                id="tone"
                name="tone"
                v-model="tone"
                class="h-9 w-fit rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
            >
                <option
                    v-for="option in documentTones"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <p class="text-xs text-muted-foreground">
                {{ documentTones.find((o) => o.value === tone)?.description }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Button
                v-for="documentType in documentTypes"
                :key="documentType.value"
                type="submit"
                name="type"
                :value="documentType.value"
                :disabled="processing"
            >
                Draft {{ documentType.label }}
            </Button>
        </div>
        <InputError :message="errors.type || errors.style || errors.tone" />
    </Form>
</template>
