<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AdjacentSpecies from '@/Components/AdjacentSpecies.vue';
import MediaImage from '@/Components/MediaImage.vue';
import SpottingMap from '@/Components/SpottingMap.vue';
import SpottingPhoto from '@/Components/SpottingPhoto.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { ImageSize, MapPoint, PageProps, SpeciesLink } from '@/types';

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
        size: ImageSize | null;
        notes: string | null;
        comment: string | null;
        location: string | null;
        group: string | null;
        spottedAt: string | null;
    }[];
    mapPoints: MapPoint[];
    journal: {
        id: number;
        spottedAt: string | null;
        location: string | null;
        owner: string | null;
        group: string | null;
        notes: string | null;
    }[];
    adjacent: {
        previous: SpeciesLink | null;
        next: SpeciesLink | null;
    };
}>();

const page = usePage<PageProps>();

// Built the same way as Challenges/Month.vue, so the two pages name months alike.
const monthName = computed((): string | null =>
    props.challenge.month
        ? new Date(2000, props.challenge.month - 1, 1).toLocaleString('en-GB', { month: 'long' })
        : null,
);

// Cards fade in a row at a time as they scroll into view.
const { container: spottingsGrid } = useScrollReveal();
</script>

<template>
    <Head :title="props.challenge.title" />

    <SiteLayout>
        <article>
            <header class="mb-6">
                <h1 class="u-display text-step-3 sm:text-step-4">{{ props.challenge.title }}</h1>
                <p v-if="props.challenge.category" class="text-ink-soft mt-1">
                    {{ props.challenge.category }}
                </p>
            </header>

            <!--
                The photograph is the point of a species page, so it runs the full
                width of the container. The crop widens with the screen: a 16/6
                band on a phone would show almost nothing of the subject.
            -->
            <figure>
                <MediaImage
                    :src="props.challenge.image"
                    :alt="props.challenge.imageName ?? props.challenge.title"
                    class="border-bark/30 aspect-4/3 w-full border object-cover sm:aspect-2/1 lg:aspect-16/6"
                    loading="eager"
                />
                <figcaption v-if="props.challenge.credit" class="text-step--1 text-ink-soft mt-2">
                    Photo by {{ props.challenge.credit }}
                </figcaption>
            </figure>

            <div class="mt-10 grid gap-10 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <p v-if="props.challenge.teaser" class="text-step-1 mb-5">
                        {{ props.challenge.teaser }}
                    </p>

                    <!-- Nova stores these as Trix HTML. -->
                    <div
                        v-if="props.challenge.description"
                        class="prose-wc"
                        v-html="props.challenge.description"
                    />
                </div>

                <aside class="border-bark/30 bg-parchment-deep/40 h-fit border p-5">
                    <h2 class="u-display text-step-1">Where to find them</h2>
                    <p v-if="props.challenge.whereToFindThem" class="mt-2">
                        {{ props.challenge.whereToFindThem }}
                    </p>

                    <p
                        v-if="monthName"
                        class="border-bark/30 text-step--1 text-ink-soft mt-4 border-t pt-4"
                    >
                        Look for it in {{ monthName }}.
                    </p>

                    <Link
                        v-if="page.props.auth.group"
                        :href="`/spottings/add/${props.challenge.id}`"
                        class="bg-berry hover:bg-berry-bright text-parchment mt-5 block px-5 py-3 text-center font-bold"
                    >
                        I spotted {{ props.challenge.plural ?? 'this' }}
                    </Link>
                </aside>
            </div>
        </article>

        <section v-if="props.spottings.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                Spotted by other groups
            </h2>
            <div ref="spottingsGrid" class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <SpottingPhoto
                    v-for="spotting in props.spottings"
                    :key="spotting.id"
                    v-bind="spotting"
                />
            </div>
        </section>
        <section v-if="props.mapPoints.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                Where we've found {{ props.challenge.plural ?? props.challenge.title }}
            </h2>
            <SpottingMap
                :points="props.mapPoints"
                :label="`Map of where ${props.challenge.plural ?? props.challenge.title} have been spotted`"
                class="mt-6"
            />
        </section>

        <!--
            The journal is the only place a spotting without a photo is ever
            seen: the grid above shows gallery entries with a picture, which on
            some species is two records out of twelve.
        -->
        <section v-if="props.journal.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                {{ props.challenge.title }} spottings journal
                <span class="text-ink-soft text-step-0">({{ props.journal.length }} seen)</span>
            </h2>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[36rem] border-collapse text-left">
                    <thead>
                        <tr class="border-bark/30 text-step--1 text-ink-soft border-b">
                            <th scope="col" class="py-2 pr-4 font-bold">Last seen</th>
                            <th scope="col" class="py-2 pr-4 font-bold">Location</th>
                            <th scope="col" class="py-2 pr-4 font-bold">Spotted by</th>
                            <th scope="col" class="py-2 pr-4 font-bold">Group</th>
                            <th scope="col" class="py-2 font-bold">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="entry in props.journal"
                            :key="entry.id"
                            class="border-bark/30 border-b"
                        >
                            <td class="py-2 pr-4 whitespace-nowrap">{{ entry.spottedAt }}</td>
                            <td class="py-2 pr-4">{{ entry.location }}</td>
                            <td class="py-2 pr-4">{{ entry.owner }}</td>
                            <td class="py-2 pr-4">{{ entry.group }}</td>
                            <td class="py-2">{{ entry.notes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <AdjacentSpecies :previous="props.adjacent.previous" :next="props.adjacent.next" />
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
