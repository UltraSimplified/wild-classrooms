<script setup lang="ts">
import { ref, watch } from 'vue';

import type { ImageSize } from '@/types';

/*
 * A photo whose file is missing.
 *
 * A bare <img> that 404s does not disappear: the browser draws the element at
 * whatever size the layout gives it, borders and all, and prints the alt text
 * inside. On the home page, where the photo is the whole card, that reads as
 * an empty panel with a species name in it. Hiding the element is both
 * tidier and more honest — nothing is shown where nothing exists.
 *
 * `missing` lets a parent drop the surrounding card or link as well, so it
 * does not leave an empty clickable box behind.
 */
const props = withDefaults(
    defineProps<{
        src?: string | null;
        alt?: string | null;
        loading?: 'lazy' | 'eager';
        // The file's own size. With these the browser holds the image's
        // space open before it arrives, instead of laying it out at zero
        // height and shifting everything below once it loads.
        size?: ImageSize | null;
    }>(),
    { src: null, alt: null, loading: 'lazy', size: null },
);

const emit = defineEmits<{ missing: [] }>();

const failed = ref(false);

// Vue reuses the element as a list re-renders, so a failure recorded for one
// photo must not blank the next one to occupy the same slot.
watch(
    () => props.src,
    () => {
        failed.value = false;
    },
);
</script>

<template>
    <img
        v-if="props.src && !failed"
        :src="props.src"
        :alt="props.alt ?? ''"
        :loading="props.loading"
        :width="props.size?.width"
        :height="props.size?.height"
        @error="
            failed = true;
            emit('missing');
        "
    />
</template>
