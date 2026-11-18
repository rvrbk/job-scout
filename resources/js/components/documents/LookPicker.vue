<script setup lang="ts">
import { Label } from '@/components/ui/label';
import type { StyleOption } from '@/types';

defineProps<{
    documentFonts: StyleOption[];
    documentStyles: StyleOption[];
}>();

const font = defineModel<string>('font', { required: true });
const style = defineModel<string>('style', { required: true });
</script>

<template>
    <div class="flex flex-wrap gap-x-6 gap-y-3">
        <div class="grid gap-1">
            <Label for="font">Font</Label>
            <select
                id="font"
                name="font"
                v-model="font"
                class="h-9 w-fit rounded-md border border-input bg-transparent px-3 text-sm shadow-xs dark:bg-input/30"
            >
                <option
                    v-for="option in documentFonts"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <p class="text-xs text-muted-foreground">
                {{ documentFonts.find((o) => o.value === font)?.description }}
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
