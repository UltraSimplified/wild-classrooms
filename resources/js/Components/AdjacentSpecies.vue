<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import MediaImage from '@/Components/MediaImage.vue';
import type { SpeciesLink } from '@/types';

/*
 * Steps sideways through a month's five species, as a loop — the last one's
 * next is the first. This replaces a single "other things to look for this
 * month" link; the month page is already in the main navigation twice, so what
 * the page actually lacked was a way to move between neighbours without going
 * back up a level.
 *
 * The photographs are decorative: each sits beside the name it belongs to, so
 * giving them alt text would have a screen reader read every species twice.
 */
const props = defineProps<{ previous: SpeciesLink | null; next: SpeciesLink | null }>();
</script>

<template>
    <nav
        v-if="props.previous || props.next"
        aria-label="Other species to find this month"
        class="mt-12 flex items-center justify-between gap-4"
    >
        <Link
            v-if="props.previous"
            :href="`/spottings/${props.previous.id}/${props.previous.slug}`"
            class="hover:text-leaf flex items-center gap-3"
        >
            <span class="text-ink-soft shrink-0" aria-hidden="true">&larr;</span>
            <MediaImage
                :src="props.previous.image"
                alt=""
                class="border-bark/30 size-12 shrink-0 border object-cover sm:size-14"
            />
            <span class="u-display text-step-0">{{ props.previous.title }}</span>
        </Link>
        <Link
            v-if="props.next"
            :href="`/spottings/${props.next.id}/${props.next.slug}`"
            class="hover:text-leaf ml-auto flex items-center gap-3"
        >
            <span class="u-display text-step-0 text-right">{{ props.next.title }}</span>
            <MediaImage
                :src="props.next.image"
                alt=""
                class="border-bark/30 size-12 shrink-0 border object-cover sm:size-14"
            />
            <span class="text-ink-soft shrink-0" aria-hidden="true">&rarr;</span>
        </Link>
    </nav>
</template>
