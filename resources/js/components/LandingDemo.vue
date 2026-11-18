<script setup lang="ts">
import {
    useIntersectionObserver,
    useIntervalFn,
    usePreferredReducedMotion,
} from "@vueuse/core";
import {
    AlertTriangle,
    Check,
    Download,
    FileText,
    Pause,
    Play,
    Search,
} from "@lucide/vue";
import { computed, ref, watch } from "vue";

type Scene = {
    id: string;
    label: string;
    title: string;
    description: string;
};

const scenes: Scene[] = [
    {
        id: "ledger",
        label: "1. Ledger",
        title: "Tell it what you have done",
        description:
            "Type it like you would tell a friend, or import your CV. It is split into single facts, and every fact starts unconfirmed until you say it is true.",
    },
    {
        id: "match",
        label: "2. Match",
        title: "See which jobs actually fit",
        description:
            "Postings from several sources, or a vacancy you paste or link, are scored against your confirmed facts, with the reason and the gaps.",
    },
    {
        id: "draft",
        label: "3. Draft",
        title: "Every sentence shows its receipt",
        description:
            "A CV or cover letter is written from your facts. Each sentence is checked against the fact it cites. Anything unsupported is flagged, not exported.",
    },
    {
        id: "export",
        label: "4. Export",
        title: "Approve it, then download",
        description:
            "Pick a colour style, a font and a tone, or match your own writing. You approve the draft, then export a PDF or Markdown file.",
    },
];

const SCENE_MS = 7000;
const TICK_MS = 100;

const reducedMotion = usePreferredReducedMotion();
const animated = computed(() => reducedMotion.value !== "reduce");

const root = ref<HTMLElement | null>(null);
const visible = ref(false);
const userPaused = ref(false);
const hovering = ref(false);
const scene = ref(0);
const elapsed = ref(0);

useIntersectionObserver(
    root,
    ([entry]) => {
        visible.value = entry?.isIntersecting ?? false;
    },
    { threshold: 0.4 },
);

const playing = computed(
    () =>
        animated.value && visible.value && !userPaused.value && !hovering.value,
);

const { pause, resume } = useIntervalFn(
    () => {
        elapsed.value += TICK_MS;
        if (elapsed.value >= SCENE_MS) {
            select((scene.value + 1) % scenes.length);
        }
    },
    TICK_MS,
    { immediate: false },
);

watch(
    playing,
    (isPlaying) => {
        if (isPlaying) {
            resume();
        } else {
            pause();
        }
    },
    { immediate: true },
);

function select(index: number) {
    scene.value = index;
    elapsed.value = 0;
}

const current = computed(() => scenes[scene.value]);
const progress = computed(() =>
    Math.min(100, (elapsed.value / SCENE_MS) * 100),
);

const facts = [
    { text: "Moved reporting to Postgres", confirmed: true },
    { text: "Mentored two junior developers", confirmed: true },
    { text: "Led a team of 12 engineers", confirmed: false },
];

const matches = [
    { title: "Backend Developer", company: "Northwind", score: 92 },
    { title: "Data Engineer", company: "Fabrikam", score: 74 },
    { title: "Engineering Manager", company: "Contoso", score: 38 },
];

const chips = ["Modern", "Serif font", "Warm tone"];
</script>

<template>
    <div
        ref="root"
        class="overflow-hidden rounded-2xl border bg-card shadow-sm"
        :class="{ 'demo-static': !animated }"
        @mouseenter="hovering = true"
        @mouseleave="hovering = false"
        @focusin="hovering = true"
        @focusout="hovering = false"
    >
        <div
            class="flex items-center gap-2 border-b bg-muted/40 px-4 py-2.5"
            role="tablist"
            aria-label="Product walkthrough"
        >
            <span class="mr-1 flex gap-1.5" aria-hidden="true">
                <span class="size-2.5 rounded-full bg-border" />
                <span class="size-2.5 rounded-full bg-border" />
                <span class="size-2.5 rounded-full bg-border" />
            </span>
            <button
                v-for="(item, index) in scenes"
                :key="item.id"
                type="button"
                role="tab"
                :aria-selected="scene === index"
                class="relative overflow-hidden rounded-md px-2.5 py-1 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                :class="
                    scene === index
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="select(index)"
            >
                {{ item.label }}
                <span
                    v-if="scene === index && animated"
                    class="absolute inset-x-0 bottom-0 h-0.5 origin-left bg-primary"
                    :style="{ transform: `scaleX(${progress / 100})` }"
                />
            </button>
            <button
                v-if="animated"
                type="button"
                class="ml-auto rounded-md p-1.5 text-muted-foreground hover:bg-background hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                :aria-label="
                    userPaused ? 'Play walkthrough' : 'Pause walkthrough'
                "
                @click="userPaused = !userPaused"
            >
                <Play v-if="userPaused" class="size-4" />
                <Pause v-else class="size-4" />
            </button>
        </div>

        <div class="grid gap-6 p-5 md:grid-cols-[2fr_3fr] md:p-6">
            <div class="space-y-2" aria-live="polite">
                <h3 class="text-xl font-bold tracking-tight">
                    {{ current.title }}
                </h3>
                <p class="text-sm text-muted-foreground">
                    {{ current.description }}
                </p>
            </div>

            <div
                :key="scene"
                class="min-h-64 space-y-2.5 rounded-xl border bg-muted/30 p-4 text-sm"
                role="tabpanel"
            >
                <!-- 1. Ledger -->
                <template v-if="scene === 0">
                    <p
                        class="demo-in rounded-lg border bg-background p-3 italic"
                        style="--d: 0ms"
                    >
                        “At my last job I moved our reporting to Postgres and
                        mentored two juniors. I also led a team of 12.”
                    </p>
                    <p
                        class="demo-in text-xs text-muted-foreground"
                        style="--d: 700ms"
                    >
                        becomes facts you can check
                    </p>
                    <div
                        v-for="(fact, i) in facts"
                        :key="fact.text"
                        class="demo-in flex items-center justify-between gap-3 rounded-lg border bg-background px-3 py-2"
                        :style="{ '--d': `${1200 + i * 450}ms` }"
                    >
                        <span>{{ fact.text }}</span>
                        <span
                            class="flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                fact.confirmed
                                    ? 'demo-confirm bg-primary/10 text-primary'
                                    : 'bg-muted text-muted-foreground'
                            "
                            :style="{ '--d': `${2800 + i * 600}ms` }"
                        >
                            <Check v-if="fact.confirmed" class="size-3" />
                            {{ fact.confirmed ? "Confirmed" : "Not confirmed" }}
                        </span>
                    </div>
                </template>

                <!-- 2. Match -->
                <template v-else-if="scene === 1">
                    <div
                        class="demo-in flex items-center gap-2 rounded-lg border bg-background px-3 py-2 text-muted-foreground"
                        style="--d: 0ms"
                    >
                        <Search class="size-4" />
                        Searching your sources…
                    </div>
                    <div
                        v-for="(job, i) in matches"
                        :key="job.title"
                        class="demo-in space-y-1.5 rounded-lg border bg-background p-3"
                        :style="{ '--d': `${600 + i * 500}ms` }"
                    >
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="font-semibold">
                                {{ job.title }}
                                <span class="font-normal text-muted-foreground"
                                    >· {{ job.company }}</span
                                >
                            </p>
                            <span class="text-xs font-semibold tabular-nums"
                                >{{ job.score }}%</span
                            >
                        </div>
                        <div class="h-1.5 rounded-full bg-muted">
                            <div
                                class="demo-bar h-full rounded-full"
                                :class="
                                    job.score > 60
                                        ? 'bg-primary'
                                        : 'bg-muted-foreground/50'
                                "
                                :style="{
                                    '--w': `${job.score}%`,
                                    '--d': `${1000 + i * 500}ms`,
                                }"
                            />
                        </div>
                    </div>
                    <p
                        class="demo-in text-xs text-muted-foreground"
                        style="--d: 2600ms"
                    >
                        Gap spotted: no confirmed fact about team leadership.
                    </p>
                </template>

                <!-- 3. Draft -->
                <template v-else-if="scene === 2">
                    <div
                        class="demo-in flex items-start gap-2 rounded-lg border bg-background p-3"
                        style="--d: 0ms"
                    >
                        <Check class="mt-0.5 size-4 shrink-0 text-primary" />
                        <div>
                            <p>I moved our reporting to Postgres.</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Backed by: Moved reporting to Postgres
                            </p>
                        </div>
                    </div>
                    <div
                        class="demo-in flex items-start gap-2 rounded-lg border bg-background p-3"
                        style="--d: 900ms"
                    >
                        <Check class="mt-0.5 size-4 shrink-0 text-primary" />
                        <div>
                            <p>I mentored two junior developers.</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Backed by: Mentored two junior developers
                            </p>
                        </div>
                    </div>
                    <div
                        class="demo-in flex items-start gap-2 rounded-lg border border-destructive/40 bg-destructive/5 p-3"
                        style="--d: 1800ms"
                    >
                        <AlertTriangle
                            class="mt-0.5 size-4 shrink-0 text-destructive"
                        />
                        <div>
                            <p>I led a team of 12 engineers.</p>
                            <p class="mt-1 text-xs text-destructive">
                                Flagged: the fact behind this is not confirmed.
                            </p>
                        </div>
                    </div>
                </template>

                <!-- 4. Export -->
                <template v-else>
                    <div class="demo-in flex flex-wrap gap-2" style="--d: 0ms">
                        <span
                            v-for="chip in chips"
                            :key="chip"
                            class="rounded-full border bg-background px-3 py-1 text-xs font-medium"
                            >{{ chip }}</span
                        >
                    </div>
                    <div
                        class="demo-in flex items-center gap-3 rounded-lg border bg-background p-3"
                        style="--d: 700ms"
                    >
                        <FileText class="size-8 text-muted-foreground" />
                        <div class="flex-1">
                            <p class="font-semibold">
                                Cover letter · Backend Developer
                            </p>
                            <p class="text-xs text-muted-foreground">
                                All sentences checked
                            </p>
                        </div>
                        <span
                            class="demo-confirm flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                            style="--d: 1800ms"
                        >
                            <Check class="size-3" /> Approved
                        </span>
                    </div>
                    <div class="demo-in flex gap-2" style="--d: 2400ms">
                        <span
                            class="flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground"
                        >
                            <Download class="size-3.5" /> PDF
                        </span>
                        <span
                            class="flex items-center gap-1.5 rounded-md border bg-background px-3 py-1.5 text-xs font-medium"
                        >
                            <Download class="size-3.5" /> Markdown
                        </span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.demo-in {
    opacity: 0;
    transform: translateY(8px);
    animation: demo-in 0.5s ease-out forwards;
    animation-delay: var(--d, 0ms);
}

.demo-confirm {
    animation: demo-pop 0.45s ease-out backwards;
    animation-delay: var(--d, 0ms);
}

.demo-bar {
    width: 0;
    animation: demo-bar 0.9s ease-out forwards;
    animation-delay: var(--d, 0ms);
}

@keyframes demo-in {
    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes demo-pop {
    from {
        opacity: 0;
        transform: scale(0.7);
    }
}

@keyframes demo-bar {
    to {
        width: var(--w);
    }
}

.demo-static .demo-in,
.demo-static .demo-confirm,
.demo-static .demo-bar {
    animation: none;
    opacity: 1;
    transform: none;
}

.demo-static .demo-bar {
    width: var(--w);
}

@media (prefers-reduced-motion: reduce) {
    .demo-in,
    .demo-confirm,
    .demo-bar {
        animation: none;
        opacity: 1;
        transform: none;
    }

    .demo-bar {
        width: var(--w);
    }
}
</style>
