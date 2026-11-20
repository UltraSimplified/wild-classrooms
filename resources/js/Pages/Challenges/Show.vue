<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import SpottingPhoto from '@/Components/SpottingPhoto.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { PageProps } from '@/types';

interface Challenge {
    id: number;
    title: string;
    plural: string | null;
    teaser: string | null;
    description: string | null;
    whereToFindThem: string | null;
    image: string | null;
    imageName: string | null;
    credit: string | null;
    month: number | null;
    category: string | null;
}

const props = defineProps<{
    challenge: Challenge;
    spottings: {
        id: number;
        picture: string | null;
        notes: string | null;
        comment: string | null;
        location: string | null;
        group: string | null;
        spottedAt: string | null;
    }[];
}>();

const page = usePage<PageProps>();
</script>

<template>
    <Head :title="props.challenge.title" />

    <SiteLayout>
        <article class="grid gap-10 md:grid-cols-5">
            <div class="md:col-span-2">
                <img
                    v-if="props.challenge.image"
                    :src="props.challenge.image"
                    :alt="props.challenge.imageName ?? props.challenge.title"
                    class="border-bark/30 w-full border"
                />
                <p v-if="props.challenge.credit" class="text-step--1 text-ink-soft mt-2">
                    Photo by {{ props.challenge.credit }}
                </p>
            </div>

            <div class="md:col-span-3">
                <h1 class="u-display text-step-3">{{ props.challenge.title }}</h1>
                <p v-if="props.challenge.category" class="text-ink-soft">
                    {{ props.challenge.category }}
                </p>

                <p v-if="props.challenge.teaser" class="text-step-1 mt-4">
                    {{ props.challenge.teaser }}
                </p>

                <!-- Nova stores these as Trix HTML. -->
                <div
                    v-if="props.challenge.description"
                    class="prose-wc mt-5"
                    v-html="props.challenge.description"
                />

                <section v-if="props.challenge.whereToFindThem" class="mt-6">
                    <h2 class="u-display text-step-1">Where to find them</h2>
                    <p class="mt-1">{{ props.challenge.whereToFindThem }}</p>
                </section>

                <Link
                    v-if="page.props.auth.group"
                    :href="`/spottings/add/${props.challenge.id}`"
                    class="bg-berry hover:bg-berry-bright mt-7 inline-block px-5 py-3 font-bold text-parchment"
                >
                    I spotted {{ props.challenge.plural ?? 'this' }}
                </Link>
            </div>
        </article>

        <section v-if="props.spottings.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                Spotted by other groups
            </h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <SpottingPhoto
                    v-for="spotting in props.spottings"
                    :key="spotting.id"
                    v-bind="spotting"
                />
            </div>
        </section>
    </SiteLayout>
</template>

<style scoped>
/* Trix output from Nova: keep it readable without a plugin. */
.prose-wc :deep(p) {
    margin-block: 0.75em;
    max-width: 64ch;
}
.prose-wc :deep(a) {
    text-decoration: underline;
}
.prose-wc :deep(ul) {
    list-style: disc;
    padding-left: 1.25rem;
    margin-block: 0.75em;
}
.prose-wc :deep(strong) {
    font-weight: 700;
}
</style>
