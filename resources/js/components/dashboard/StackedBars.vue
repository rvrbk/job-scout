<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Series = { label: string; values: number[] };

const props = withDefaults(
    defineProps<{
        title: string;
        categories: string[];
        series: Series[];
        horizontal?: boolean;
        /** Show every n-th category label on the x axis (vertical only). */
        labelEvery?: number;
    }>(),
    { horizontal: false, labelEvery: 1 },
);

const GAP = 2;
const MAX_THICKNESS = 24;
const RADIUS = 4;

/** Measured from the card, so text and bars keep their real pixel size. */
const width = ref(560);
const rowHeight = 36;
const height = computed(() =>
    props.horizontal ? props.categories.length * rowHeight + 16 : 230,
);
const margin = computed(() =>
    props.horizontal
        ? { top: 4, right: 36, bottom: 8, left: 112 }
        : { top: 20, right: 8, bottom: 28, left: 34 },
);
const plot = computed(() => ({
    x: margin.value.left,
    y: margin.value.top,
    w: width.value - margin.value.left - margin.value.right,
    h: height.value - margin.value.top - margin.value.bottom,
}));

const totals = computed(() =>
    props.categories.map((_, i) =>
        props.series.reduce((sum, s) => sum + (s.values[i] ?? 0), 0),
    ),
);
const peak = computed(() => Math.max(0, ...totals.value));

/** Round the axis to a clean number: 1, 2, 5 times a power of ten. */
const niceStep = (rough: number) => {
    const magnitude = 10 ** Math.floor(Math.log10(rough));
    const fraction = rough / magnitude;
    return (
        (fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10) *
        magnitude
    );
};
const step = computed(() => niceStep(Math.max(peak.value, 1) / 4));
const axisMax = computed(() =>
    Math.max(
        step.value * Math.ceil(Math.max(peak.value, 1) / step.value),
        step.value,
    ),
);
const ticks = computed(() =>
    Array.from(
        { length: Math.round(axisMax.value / step.value) + 1 },
        (_, i) => i * step.value,
    ),
);

const scale = computed(
    () => (props.horizontal ? plot.value.w : plot.value.h) / axisMax.value,
);
const slot = computed(
    () =>
        (props.horizontal ? plot.value.h : plot.value.w) /
        Math.max(props.categories.length, 1),
);
const thickness = computed(() => Math.min(MAX_THICKNESS, slot.value * 0.6));

type Segment = { path: string; series: number };

/** One path per non-empty segment; only the outermost one gets the rounded data end. */
const columns = computed(() =>
    props.categories.map((_, i) => {
        const along = i * slot.value + (slot.value - thickness.value) / 2;
        const filled = props.series
            .map((s, k) => ({ k, v: s.values[i] ?? 0 }))
            .filter((s) => s.v > 0);
        let used = 0;

        const segments: Segment[] = filled.map(({ k, v }, n) => {
            const start = used * scale.value + (n > 0 ? GAP : 0);
            const end = (used + v) * scale.value;
            used += v;
            const length = Math.max(end - start, 1);
            const round =
                n === filled.length - 1
                    ? Math.min(RADIUS, length / 2, thickness.value / 2)
                    : 0;

            return { path: shape(along, start, length, round), series: k };
        });

        return { along, segments, end: used * scale.value };
    }),
);

/** A bar from `start` over `length`, with its far end rounded. */
function shape(
    along: number,
    start: number,
    length: number,
    r: number,
): string {
    const t = thickness.value;

    if (props.horizontal) {
        const x0 = plot.value.x + start;
        const y0 = plot.value.y + along;
        const x1 = x0 + length;

        return `M${x0},${y0} H${x1 - r} ${r ? `Q${x1},${y0} ${x1},${y0 + r}` : `H${x1}`} V${y0 + t - r} ${r ? `Q${x1},${y0 + t} ${x1 - r},${y0 + t}` : ''} H${x0} Z`;
    }

    const base = plot.value.y + plot.value.h;
    const yBottom = base - start;
    const yTop = yBottom - length;
    const x0 = plot.value.x + along;
    const x1 = x0 + t;

    return `M${x0},${yBottom} V${yTop + r} ${r ? `Q${x0},${yTop} ${x0 + r},${yTop}` : `V${yTop}`} H${x1 - r} ${r ? `Q${x1},${yTop} ${x1},${yTop + r}` : `H${x1}`} V${yBottom} Z`;
}

const peakIndex = computed(() =>
    peak.value > 0 ? totals.value.indexOf(peak.value) : -1,
);

const hovered = ref<number | null>(null);
const tooltip = ref({ x: 0, y: 0 });
const wrapper = ref<HTMLElement | null>(null);

let observer: ResizeObserver | null = null;

onMounted(() => {
    if (!wrapper.value) {
        return;
    }

    width.value = wrapper.value.clientWidth || width.value;
    observer = new ResizeObserver(([entry]) => {
        width.value = Math.max(240, Math.round(entry.contentRect.width));
    });
    observer.observe(wrapper.value);
});

onBeforeUnmount(() => observer?.disconnect());

/** Keep the tooltip (about 170px wide, centred on x) inside the chart. */
const keepInside = (x: number, boxWidth: number) =>
    Math.min(Math.max(x, 88), Math.max(boxWidth - 88, 88));

const show = (i: number, event?: PointerEvent) => {
    hovered.value = i;
    const box = wrapper.value?.getBoundingClientRect();

    if (!box) {
        return;
    }

    if (event) {
        tooltip.value = {
            x: keepInside(event.clientX - box.left, box.width),
            y: event.clientY - box.top,
        };

        return;
    }

    const center = (i + 0.5) * slot.value;
    tooltip.value = props.horizontal
        ? {
              x: box.width * 0.5,
              y: ((plot.value.y + center) / height.value) * box.height,
          }
        : {
              x: keepInside(
                  ((plot.value.x + center) / width.value) * box.width,
                  box.width,
              ),
              y: box.height * 0.3,
          };
};
const hide = () => (hovered.value = null);

const hoverRect = (i: number) =>
    props.horizontal
        ? {
              x: 0,
              y: plot.value.y + i * slot.value,
              width: width.value,
              height: slot.value,
          }
        : {
              x: plot.value.x + i * slot.value,
              y: plot.value.y,
              width: slot.value,
              height: plot.value.h,
          };

const format = (n: number) =>
    n >= 1000 ? `${+(n / 1000).toFixed(1)}K` : String(n);
</script>

<template>
    <figure class="viz space-y-3">
        <div
            v-if="series.length > 1"
            class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"
        >
            <span
                v-for="(s, k) in series"
                :key="s.label"
                class="flex items-center gap-1.5"
            >
                <span
                    class="inline-block size-2.5 rounded-sm"
                    :class="`fill-s${k}`"
                />
                {{ s.label }}
            </span>
        </div>

        <div ref="wrapper" class="relative">
            <svg
                :viewBox="`0 0 ${width} ${height}`"
                :width="width"
                :height="height"
                class="block max-w-full"
                role="img"
                :aria-label="title"
                @pointerleave="hide"
            >
                <!-- grid + axis labels -->
                <g class="axis">
                    <template v-for="t in ticks" :key="t">
                        <line
                            v-if="horizontal"
                            :x1="plot.x + t * scale"
                            :x2="plot.x + t * scale"
                            :y1="plot.y"
                            :y2="plot.y + plot.h"
                            :class="t === 0 ? 'baseline' : 'grid'"
                        />
                        <line
                            v-else
                            :x1="plot.x"
                            :x2="plot.x + plot.w"
                            :y1="plot.y + plot.h - t * scale"
                            :y2="plot.y + plot.h - t * scale"
                            :class="t === 0 ? 'baseline' : 'grid'"
                        />
                        <text
                            v-if="!horizontal"
                            :x="plot.x - 8"
                            :y="plot.y + plot.h - t * scale + 3.5"
                            text-anchor="end"
                            class="tick"
                        >
                            {{ format(t) }}
                        </text>
                    </template>
                </g>

                <!-- hover wash behind the bars -->
                <rect
                    v-if="hovered !== null"
                    v-bind="hoverRect(hovered)"
                    class="wash"
                    rx="4"
                />

                <!-- bars -->
                <g v-for="(column, i) in columns" :key="categories[i]">
                    <path
                        v-for="segment in column.segments"
                        :key="segment.series"
                        :d="segment.path"
                        :class="`fill-s${segment.series}`"
                    />

                    <!-- the peak is labelled; everything else lives in the tooltip and the table -->
                    <text
                        v-if="i === peakIndex && !horizontal"
                        :x="plot.x + column.along + thickness / 2"
                        :y="plot.y + plot.h - column.end - 6"
                        text-anchor="middle"
                        class="value"
                    >
                        {{ totals[i] }}
                    </text>
                    <text
                        v-if="horizontal"
                        :x="plot.x + column.end + 8"
                        :y="plot.y + column.along + thickness / 2 + 4"
                        class="value"
                    >
                        {{ totals[i] }}
                    </text>
                </g>

                <!-- category labels -->
                <g class="axis">
                    <template v-for="(label, i) in categories" :key="label">
                        <text
                            v-if="horizontal"
                            :x="plot.x - 12"
                            :y="plot.y + (i + 0.5) * slot + 4"
                            text-anchor="end"
                            class="category"
                        >
                            {{ label }}
                        </text>
                        <text
                            v-else-if="i % labelEvery === 0"
                            :x="plot.x + (i + 0.5) * slot"
                            :y="plot.y + plot.h + 18"
                            text-anchor="middle"
                            class="tick"
                        >
                            {{ label }}
                        </text>
                    </template>
                </g>

                <!-- hit targets: bigger than the marks, keyboard reachable -->
                <rect
                    v-for="(label, i) in categories"
                    :key="`hit-${label}`"
                    v-bind="hoverRect(i)"
                    fill="transparent"
                    tabindex="0"
                    :aria-label="`${label}: ${series.map((s) => `${s.label} ${s.values[i] ?? 0}`).join(', ')}`"
                    class="outline-none"
                    @pointermove="show(i, $event)"
                    @focus="show(i)"
                    @blur="hide"
                />
            </svg>

            <div
                v-if="hovered !== null"
                class="pointer-events-none absolute z-10 min-w-36 -translate-x-1/2 -translate-y-full rounded-lg border bg-popover px-3 py-2 text-xs shadow-md"
                :style="{ left: `${tooltip.x}px`, top: `${tooltip.y - 10}px` }"
            >
                <div class="mb-1 text-muted-foreground">
                    {{ categories[hovered] }}
                </div>
                <div
                    v-for="(s, k) in series"
                    :key="s.label"
                    class="flex items-center justify-between gap-4"
                >
                    <span
                        class="flex items-center gap-1.5 text-muted-foreground"
                    >
                        <span
                            class="inline-block h-0.5 w-3 rounded"
                            :class="`fill-s${k}`"
                        />
                        {{ s.label }}
                    </span>
                    <span
                        class="font-display text-sm font-bold text-foreground"
                        >{{ s.values[hovered] ?? 0 }}</span
                    >
                </div>
            </div>
        </div>

        <table class="sr-only">
            <caption>
                {{
                    title
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col" />
                    <th v-for="s in series" :key="s.label" scope="col">
                        {{ s.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(label, i) in categories" :key="label">
                    <th scope="row">{{ label }}</th>
                    <td v-for="s in series" :key="s.label">
                        {{ s.values[i] ?? 0 }}
                    </td>
                </tr>
            </tbody>
        </table>
    </figure>
</template>

<style scoped>
.viz {
    --viz-1: var(--primary);
    --viz-2: color-mix(
        in oklab,
        var(--muted-foreground) 45%,
        var(--background)
    );
}
.fill-s0 {
    fill: var(--viz-1);
    background-color: var(--viz-1);
}
.fill-s1 {
    fill: var(--viz-2);
    background-color: var(--viz-2);
}
.grid {
    stroke: var(--border);
    stroke-width: 1;
}
.baseline {
    stroke: color-mix(in oklab, var(--border) 60%, var(--muted-foreground));
    stroke-width: 1;
}
.tick,
.category {
    fill: var(--muted-foreground);
    font-size: 11px;
}
.category {
    fill: var(--foreground);
    font-size: 12px;
}
.value {
    fill: var(--foreground);
    font-size: 12px;
    font-weight: 700;
    font-family: var(--font-display);
}
.wash {
    fill: var(--accent);
    opacity: 0.55;
}
</style>
