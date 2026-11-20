<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';

import MediaImage from '@/Components/MediaImage.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { PageProps } from '@/types';

interface GallerySpotting {
    id: number;
    picture: string | null;
    notes: string | null;
    comment: string | null;
    challenge: string | null;
    challengeId: number | null;
    group: string | null;
    likes: number;
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
</script>

<template>
    <Head title="Gallery" />

    <SiteLayout title="Gallery" lede="Photos and drawings groups have chosen to share.">
        <div class="columns-1 gap-5 sm:columns-2 lg:columns-3 [&>*]:mb-5">
            <figure
                v-for="spotting in props.spottings"
                :key="spotting.id"
                class="border-bark/30 bg-parchment-deep/40 break-inside-avoid border"
            >
                <Link v-if="spotting.challengeId" :href="`/spottings/${spotting.challengeId}`">
                    <MediaImage
                        :src="spotting.picture"
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

                    <button
                        type="button"
                        class="border-bark/40 hover:border-berry hover:text-berry shrink-0 border px-2 py-1"
                        :aria-label="`Like this spotting of ${spotting.challenge ?? 'wildlife'}`"
                        @click="like(spotting)"
                    >
                        <span aria-hidden="true">&#9825;</span>
                        <span class="text-step--1 ml-1">{{ spotting.likes }}</span>
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
