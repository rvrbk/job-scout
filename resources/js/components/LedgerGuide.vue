<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ArrowRight, Check } from "@lucide/vue";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import { index as ledgerIndex } from "@/routes/ledger";

const props = defineProps<{
    /** Facts in the ledger, confirmed or not. */
    totalFacts: number;
    /** Facts that are confirmed, under a confirmed entry. */
    usableFacts: number;
    /** Show the button that opens the ledger (not needed on the ledger itself). */
    linkToLedger?: boolean;
}>();

const filled = computed(() => props.totalFacts > 0);
const confirmed = computed(() => props.usableFacts > 0);

const steps = computed(() => [
    {
        title: "Tell Job Scout about your experience",
        done: filled.value,
        body: [
            "Upload your CV (PDF, Word or text, up to 5 MB), or type a few lines about yourself the way you would tell a friend. Job Scout reads it and sorts it into entries (roles, projects, education, skills) and single, checkable facts.",
            "This takes a minute or so; the page updates by itself when it is done. You can add more at any time, for example when you start a new job.",
        ],
        tips: [
            'Be specific. "Cut checkout load time from 4s to 1.2s by caching in Redis" is worth far more than "improved performance".',
            "Name the tools, languages and numbers you worked with. They are what jobs get matched on.",
            "Nothing is invented: if it is not in what you wrote, it is not in your ledger.",
        ],
    },
    {
        title: "Confirm what is true",
        done: confirmed.value,
        body: [
            'Everything the import adds starts as "Not confirmed". Read each fact, fix what is off with the pencil, then press the ✓ button on the fact. Press ✓ on the entry too: a fact only counts once both it and its entry are confirmed.',
            "Only confirmed facts are ever used to match jobs or write a CV or cover letter. That is the point: nothing is said about you that you have not checked.",
        ],
        tips: [
            "Delete anything wrong or that you would rather not mention.",
            "A handful of confirmed facts is enough to start; confirm more later for better matches.",
        ],
    },
    {
        title: "Get matched with jobs",
        done: false,
        body: [
            "The moment you have confirmed facts, your first job search starts automatically. Job Scout looks through its job sources, scores every posting from 0 to 100 against your confirmed facts and skills, and tells you why: what fits and what is missing. Matches then appear under Jobs, best score first.",
            "After that it searches again every day, so new matches keep arriving without you doing anything. The more you have confirmed, the better the matches. From any job you can draft a CV or cover letter that uses only your confirmed facts.",
        ],
        tips: [],
    },
]);

// The first step that is not done yet is the one to work on now.
const currentIndex = computed(() => {
    const index = steps.value.findIndex((step) => !step.done);

    return index === -1 ? steps.value.length - 1 : index;
});
</script>

<template>
    <section
        class="space-y-5 rounded-xl border border-primary/40 bg-card p-5"
        aria-labelledby="ledger-guide-title"
    >
        <div class="space-y-1">
            <h2 id="ledger-guide-title" class="text-lg font-bold">
                Start here: fill your facts ledger
            </h2>
            <p class="text-sm text-muted-foreground">
                Your ledger is what Job Scout knows about you. It decides which
                jobs are a good match and what your CV and cover letters can
                say. Three steps get you to your first matches.
            </p>
        </div>

        <ol class="space-y-4">
            <li
                v-for="(step, index) in steps"
                :key="step.title"
                class="flex gap-3"
            >
                <span
                    class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold"
                    :class="
                        step.done
                            ? 'border-primary bg-primary text-primary-foreground'
                            : index === currentIndex
                              ? 'border-primary text-primary'
                              : 'text-muted-foreground'
                    "
                >
                    <Check v-if="step.done" class="size-3.5" />
                    <template v-else>{{ index + 1 }}</template>
                </span>
                <div class="space-y-2 text-sm">
                    <h3
                        class="font-semibold"
                        :class="index > currentIndex && 'text-muted-foreground'"
                    >
                        {{ step.title }}
                    </h3>
                    <template
                        v-if="index === currentIndex || step.done === false"
                    >
                        <p
                            v-for="paragraph in step.body"
                            :key="paragraph"
                            class="text-muted-foreground"
                        >
                            {{ paragraph }}
                        </p>
                        <ul
                            v-if="step.tips.length && index === currentIndex"
                            class="list-disc space-y-1 pl-5 text-muted-foreground"
                        >
                            <li v-for="tip in step.tips" :key="tip">
                                {{ tip }}
                            </li>
                        </ul>
                    </template>
                </div>
            </li>
        </ol>

        <Button v-if="linkToLedger" as-child>
            <Link :href="ledgerIndex()"> Fill your ledger <ArrowRight /> </Link>
        </Button>
    </section>
</template>
