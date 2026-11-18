<script setup lang="ts">
import { Label } from "@/components/ui/label";
import type { StyleOption } from "@/types";

defineProps<{
    documentLayouts: StyleOption[];
    documentStyles: StyleOption[];
}>();

const layout = defineModel<string>("layout", { required: true });
const style = defineModel<string>("style", { required: true });
</script>

<template>
    <div class="flex flex-wrap gap-x-6 gap-y-3">
        <div class="grid gap-1">
            <Label for="layout">Layout</Label>
            <select
                id="layout"
                name="layout"
                v-model="layout"
                class="h-9 w-fit rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
            >
                <option
                    v-for="option in documentLayouts"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <p class="text-xs text-muted-foreground">
                {{
                    documentLayouts.find((o) => o.value === layout)?.description
                }}
            </p>
        </div>
        <div class="grid gap-1">
            <Label for="style">Colours</Label>
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
    </div>
</template>
