<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

import MediaImage from '@/Components/MediaImage.vue';

/*
 * One species to find. Styled as a pinned specimen label rather than a generic
 * rounded card: square corners, a hairline rule under the name, and the photo
 * sitting flush at the top.
 */
const props = defineProps<{
    id: number;
    title: string;
    slug: string;
    teaser?: string | null;
    image?: string | null;
    category?: string | null;
    spotted?: boolean;
}>();

// A photo whose file has gone falls back to the same placeholder as a species
// that never had one.
const photoMissing = ref(false);
</script>

<template>
    <article
        class="border-bark/30 bg-parchment-deep/40 flex flex-col border"
        :class="props.spotted ? 'border-leaf' : ''"
    >
        <Link :href="`/spottings/${props.id}/${props.slug}`" class="block">
            <MediaImage
                v-if="props.image && !photoMissing"
                :src="props.image"
                :alt="props.title"
                class="aspect-4/3 w-full object-cover"
                @missing="photoMissing = true"
            />
            <div
                v-else
                class="bg-bark/10 text-ink-soft aspect-4/3 w-full place-content-center text-center"
            >
                <span class="text-step--1">No photo yet</span>
            </div>
        </Link>

        <div class="flex grow flex-col p-4">
            <h3 class="u-display text-step-1">
                <Link :href="`/spottings/${props.id}/${props.slug}`" class="hover:text-leaf">
                    {{ props.title }}
                </Link>
            </h3>

            <p v-if="props.category" class="text-step--1 text-ink-soft !max-w-none">
                {{ props.category }}
            </p>

            <p v-if="props.teaser" class="text-step--1 mt-2 !max-w-none grow">
                {{ props.teaser }}
            </p>

            <p v-if="props.spotted" class="text-leaf text-step--1 mt-3 !max-w-none font-bold">
                Spotted
            </p>
        </div>
    </article>
</template>
