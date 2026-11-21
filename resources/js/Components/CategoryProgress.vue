<script setup lang="ts">
import { computed } from 'vue';

/*
 * The five categories for a month, and whether this member has found one in
 * each. This is the structural device that carries the information on a
 * member's page: it maps one-to-one onto the leaf-per-category mechanic, so a
 * filled row means a fully leafed month. Not a progress bar — the tree is the
 * progress indicator.
 *
 * The heading names the month because the tree beside it does not: a tree
 * carrying a dozen leaves from earlier months next to "0 of 5 found" reads as
 * a contradiction until you know the count is for one month only.
 */
const props = defineProps<{
    monthAbbreviation: string;
    monthName: string;
    spotted: Record<string, Record<string, boolean>>;
    categories?: string[];
}>();

const ORDER = ['Trees', 'Birds', 'Butterflies & moths', 'Minibeasts', 'Plants'];

const rows = computed(() => {
    const found = props.spotted[props.monthAbbreviation] ?? {};

    return (props.categories ?? ORDER).map((category) => ({
        category,
        found: found[category] === true,
    }));
});

const total = computed(() => rows.value.filter((r) => r.found).length);
</script>

<template>
    <div class="border-bark/30 bg-parchment-deep/40 border p-4">
        <h3 class="u-display text-step-1">
            {{ total }} of {{ rows.length }} found in {{ props.monthName }}
        </h3>

        <ul class="mt-3 space-y-1.5">
            <li
                v-for="row in rows"
                :key="row.category"
                class="flex items-baseline gap-2"
                :class="row.found ? 'text-ink' : 'text-ink-soft'"
            >
                <span
                    class="inline-block size-3 shrink-0 translate-y-px rounded-full border"
                    :class="
                        row.found ? 'border-leaf-deep bg-leaf' : 'border-bark/50 bg-transparent'
                    "
                    aria-hidden="true"
                />
                <span>{{ row.category }}</span>
                <span v-if="row.found" class="sr-only">found</span>
                <span v-else class="sr-only">not found yet</span>
            </li>
        </ul>
    </div>
</template>
