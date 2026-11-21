<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';

import MediaImage from '@/Components/MediaImage.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { ImageSize, PageProps } from '@/types';

interface GallerySpotting {
    id: number;
    picture: string | null;
    size: ImageSize | null;
    notes: string | null;
    comment: string | null;
    challenge: string | null;
    challengeId: number | null;
    group: string | null;
    likes: number;
    /** Liked by whoever is looking: their account, or a guest's session. */
    liked: boolean;
}

const props = defineProps<{ spottings: GallerySpotting[] }>();
const page = usePage<PageProps>();

/*
 * Liking is a POST now. The old site used a GET link, so it was exempt from
 * CSRF and any crawler walking the page inflated every count.
 */
const like = (spotting: GallerySpotting) => {
    router.post(`/gallery/${spotting.id}/like`, {}, { preserveScroll: true, preserveState: true });
};

// Cards fade in a row at a time as they scroll into view.
const { container: gallery } = useScrollReveal();
</script>

<template>
    <Head title="Gallery" />

    <SiteLayout title="Gallery" lede="Photos and drawings groups have chosen to share.">
        <div ref="gallery" class="columns-1 gap-5 sm:columns-2 lg:columns-3 [&>*]:mb-5">
            <figure
                v-for="spotting in props.spottings"
                :key="spotting.id"
                class="border-bark/30 bg-parchment-deep/40 break-inside-avoid border"
            >
                <Link v-if="spotting.challengeId" :href="`/spottings/${spotting.challengeId}`">
                    <MediaImage
                        :src="spotting.picture"
                        :size="spotting.size"
                        :alt="spotting.challenge ?? 'A spotting'"
                        class="w-full"
                    />
                </Link>

                <figcaption class="flex items-start gap-3 p-3">
                    <div class="grow">
                        <p v-if="spotting.challenge" class="u-display text-step-1 !max-w-none">
                            {{ spotting.challenge }}
                        </p>
                        <p v-if="spotting.notes" class="text-step--1 mt-1 !max-w-none">
                            {{ spotting.notes }}
                        </p>
                        <p
                            v-if="spotting.group"
                            class="text-step--1 text-ink-soft mt-1 !max-w-none"
                        >
                            {{ spotting.group }}
                        </p>
                    </div>

                    <!--
                        Liking toggles for everyone, so this is a toggle button
                        and says whether it is pressed. The heart is drawn
                        rather than typed: the ♡ and ♥ characters come from
                        whichever font has them and can turn into an emoji,
                        where this one can simply be filled.
                    -->
                    <button
                        type="button"
                        class="hover:border-berry hover:text-berry inline-flex shrink-0 items-center gap-1 border px-2 py-1"
                        :class="spotting.liked ? 'border-berry text-berry' : 'border-bark/40'"
                        :aria-label="`Like this spotting of ${spotting.challenge ?? 'wildlife'}`"
                        :aria-pressed="spotting.liked"
                        @click="like(spotting)"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            class="size-4"
                            :fill="spotting.liked ? 'currentColor' : 'none'"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M21 8.25c0-2.49-2.1-4.5-4.69-4.5-1.93 0-3.6 1.13-4.31 2.73-.72-1.6-2.38-2.73-4.31-2.73C5.1 3.75 3 5.76 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"
                            />
                        </svg>
                        <span class="text-step--1">{{ spotting.likes }}</span>
                    </button>
                </figcaption>
            </figure>
        </div>

        <p v-if="!props.spottings.length" class="text-step-1">
            Nothing in the gallery yet.
            <Link
                v-if="page.props.auth.group"
                href="/spottings/add"
                class="hover:text-leaf underline"
            >
                Add the first spotting.
            </Link>
        </p>
    </SiteLayout>
</template>
