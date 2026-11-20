<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import MediaImage from '@/Components/MediaImage.vue';
import SpeciesCard from '@/Components/SpeciesCard.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { PageProps } from '@/types';

interface Challenge {
    id: number;
    title: string;
    slug: string;
    month: number | null;
    image: string | null;
    category: string | null;
}

interface Featured {
    id: number;
    picture: string | null;
    notes: string | null;
    comment: string | null;
    challenge: string | null;
    challengeId: number | null;
    group: string | null;
}

const props = defineProps<{
    challenges: Challenge[];
    featured: Featured[];
    mapGroups: { id: number; title: string; slug: string }[];
}>();

const page = usePage<PageProps>();

// Featured photos whose file turned out to be missing. The photo is the whole
// card here, so the link goes with it rather than leaving an empty box.
const missing = reactive(new Set<number>());

const thisMonth = computed(() =>
    props.challenges.filter((c) => c.month === page.props.currentMonth.number),
);

/*
 * Signed-in groups see their own first tree; everyone else sees a finished
 * one, which is the thing being offered.
 */
const heroTreeSrc = computed(() => {
    const members = page.props.auth.group?.members ?? [];

    return members.length > 0 ? `/tree.svg/${members[0].id}` : '/tree.svg';
});

const heroTreeAlt = computed(() =>
    (page.props.auth.group?.members ?? []).length > 0
        ? 'Your woodland tree, with a leaf for every species spotted'
        : 'A tree after a full year of spotting, carrying all sixty leaves',
);
</script>

<template>
    <Head title="Spot 60 species in a year" />

    <SiteLayout>
        <!--
          The tree is the hero: it is what the project actually gives you, and
          it doubles as the progress indicator, so there is no percentage bar
          anywhere on this site.
        -->
        <section class="grid items-center gap-10 md:grid-cols-5">
            <div class="md:col-span-3">
                <h1 class="u-display text-step-3 sm:text-step-4">
                    Spot 60 species in a year
                </h1>
                <p class="text-step-1 mt-4">
                    Five plants and animals to find each month, around Stroud and wherever
                    else you go. Tick off what you see and your tree grows a leaf for it.
                </p>
                <p class="text-step-0 text-ink-soft mt-3">
                    Schools, families, Scout and Guide groups — anyone can join. It takes
                    about five minutes a week.
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <Link
                        v-if="!page.props.auth.user"
                        href="/register"
                        class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment"
                    >
                        Join in
                    </Link>
                    <Link
                        v-else-if="page.props.auth.group"
                        :href="`/groups/woodland/${page.props.auth.group.id}/${page.props.auth.group.slug}`"
                        class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment"
                    >
                        Our woodland
                    </Link>
                    <Link
                        v-else
                        href="/profiles/onboarding"
                        class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment"
                    >
                        Set up your group
                    </Link>

                    <Link href="/gallery" class="text-ink underline hover:text-leaf">
                        See what people have spotted
                    </Link>
                </div>
            </div>

            <div class="md:col-span-2">
                <img
                    :src="heroTreeSrc"
                    :alt="heroTreeAlt"
                    class="mx-auto w-full max-w-sm"
                />
            </div>
        </section>

        <section v-if="thisMonth.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                {{ page.props.currentMonth.name }} — five to find
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <SpeciesCard
                    v-for="challenge in thisMonth"
                    :key="challenge.id"
                    v-bind="challenge"
                />
            </div>
        </section>

        <section v-if="props.featured.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                Spotted by the groups
            </h2>

            <div class="mt-6 columns-2 gap-4 md:columns-3 lg:columns-4 [&>*]:mb-4">
                <Link
                    v-for="spotting in props.featured"
                    v-show="!missing.has(spotting.id)"
                    :key="spotting.id"
                    :href="
                        spotting.challengeId ? `/spottings/${spotting.challengeId}` : '/gallery'
                    "
                    class="block break-inside-avoid"
                >
                    <MediaImage
                        :src="spotting.picture"
                        :alt="spotting.challenge ?? 'A spotting'"
                        class="border-bark/30 w-full border"
                        @missing="missing.add(spotting.id)"
                    />
                </Link>
            </div>
        </section>

        <section v-if="props.mapGroups.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                {{ props.mapGroups.length }} woodlands so far
            </h2>
            <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2">
                <li v-for="group in props.mapGroups" :key="group.id">
                    <Link
                        :href="`/groups/woodland/${group.id}/${group.slug}`"
                        class="underline hover:text-leaf"
                    >
                        {{ group.title }}
                    </Link>
                </li>
            </ul>
        </section>
    </SiteLayout>
</template>
