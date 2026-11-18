import { ref } from 'vue';

const KEY = 'job-scout-tour';

type Saved = { step: number; paused: boolean };

const read = (): Saved | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        return JSON.parse(window.sessionStorage.getItem(KEY) ?? 'null');
    } catch {
        return null;
    }
};

const saved = read();

// Module-level state, so the tour survives page visits and can be driven from any page.
const open = ref(false);
const current = ref(saved?.step ?? 0);
const paused = ref(saved?.paused ?? false);

const persist = () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        if (paused.value) {
            window.sessionStorage.setItem(
                KEY,
                JSON.stringify({ step: current.value, paused: true }),
            );
        } else {
            window.sessionStorage.removeItem(KEY);
        }
    } catch {
        // Storage can be blocked; the tour then simply does not survive a reload.
    }
};

export function useTour() {
    /** Open the tour from the first step. */
    const start = () => {
        current.value = 0;
        paused.value = false;
        open.value = true;
        persist();
    };

    /** Close the dialog but keep the place, so it can be continued from any page. */
    const pause = (step: number = current.value) => {
        current.value = step;
        paused.value = true;
        open.value = false;
        persist();
    };

    const resume = () => {
        paused.value = false;
        open.value = true;
        persist();
    };

    /** End the tour for good. */
    const finish = () => {
        paused.value = false;
        open.value = false;
        current.value = 0;
        persist();
    };

    return { open, current, paused, start, pause, resume, finish };
}
