<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { ref } from "vue";
import LookPicker from "@/components/documents/LookPicker.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import type { Option, StyleOption, ToneOption } from "@/types";

const props = defineProps<{
    // The Wayfinder form for the route that creates the document.
    action: {
        action: string;
        method: "get" | "post" | "put" | "patch" | "delete";
    };
    documentTypes: Option[];
    documentStyles: StyleOption[];
    documentLayouts: StyleOption[];
    documentTones: ToneOption[];
    // The look this user gets: what they last chose, else one assigned to them.
    defaultStyle: string;
    defaultLayout: string;
    preferredTone: string | null;
}>();

const style = ref(props.defaultStyle);
const layout = ref(props.defaultLayout);
const choosingLook = ref(false);
const tone = ref(
    props.preferredTone ?? props.documentTones[0]?.value ?? "professional",
);
</script>

<template>
    <Form v-bind="action" class="space-y-3" v-slot="{ errors, processing }">
        <div class="space-y-2">
            <p class="text-sm text-muted-foreground">
                PDF look:
                {{ documentLayouts.find((o) => o.value === layout)?.label }}
                layout,
                {{ documentStyles.find((o) => o.value === style)?.label }}
                colours.
                <button
                    v-if="!choosingLook"
                    type="button"
                    class="underline"
                    @click="choosingLook = true"
                >
                    Not happy? Choose a different style
                </button>
            </p>
            <!-- v-show, so the chosen values are submitted even while hidden. -->
            <LookPicker
                v-show="choosingLook"
                v-model:layout="layout"
                v-model:style="style"
                :document-layouts="documentLayouts"
                :document-styles="documentStyles"
            />
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
        <InputError
            :message="
                errors.type || errors.layout || errors.style || errors.tone
            "
        />
    </Form>
</template>
