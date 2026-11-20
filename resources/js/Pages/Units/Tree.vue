<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import CategoryProgress from '@/Components/CategoryProgress.vue';
import SpeciesCard from '@/Components/SpeciesCard.vue';
import SpottingPhoto from '@/Components/SpottingPhoto.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

interface Challenge {
    id: number;
    title: string;
    slug: string;
    teaser: string | null;
    image: string | null;
    category: string | null;
}

interface Spotting {
    id: number;
    picture: string | null;
    notes: string | null;
    comment: string | null;
    location: string | null;
    what3words: string | null;
    merit: boolean;
    challenge: string | null;
    challengeId: number | null;
    spottedAt: string | null;
}

const props = defineProps<{
    unit: { id: number; title: string; slug: string };
    group: { id: number | null; title: string | null; slug: string };
    month: number;
    monthName: string;
    challenges: Challenge[];
    nextMonth: number;
    nextChallenges: Challenge[];
    spotted: Record<string, Record<string, boolean>>;
    spottings: Spotting[];
    isMyGroup: boolean;
}>();

const abbreviation = computed(() =>
    new Date(2000, props.month - 1, 1).toLocaleString('en-GB', { month: 'short' }),
);

const spottedCategories = computed(() => props.spotted[abbreviation.value] ?? {});

const nameOfMonth = (m: number) =>
    new Date(2000, m - 1, 1).toLocaleString('en-GB', { month: 'long' });

const previousMonth = computed(() => (props.month === 1 ? 12 : props.month - 1));
</script>

<template>
    <Head :title="`${props.unit.title}'s tree`" />

    <SiteLayout>
        <header class="mb-8">
            <h1 class="u-display text-step-3 sm:text-step-4">{{ props.unit.title }}</h1>
            <p v-if="props.group.title" class="text-ink-soft">
                <Link
                    :href="`/groups/woodland/${props.group.id}/${props.group.slug}`"
                    class="underline hover:text-leaf"
                >
                    {{ props.group.title }}
                </Link>
            </p>
        </header>

        <div class="grid items-start gap-8 md:grid-cols-5">
            <div class="md:col-span-3">
                <img
                    :src="`/tree.svg/${props.unit.id}`"
                    :alt="`${props.unit.title}'s tree, with a leaf for each species spotted`"
                    class="mx-auto w-full max-w-md"
                />
            </div>

            <div class="md:col-span-2">
                <CategoryProgress
                    :month-abbreviation="abbreviation"
                    :spotted="props.spotted"
                />

                <p class="text-step--1 text-ink-soft mt-3">
                    A leaf appears on the tree for each category found, in each month.
                </p>

                <Link
                    v-if="props.isMyGroup"
                    href="/spottings/add"
                    class="bg-berry hover:bg-berry-bright mt-5 inline-block px-5 py-3 font-bold text-parchment"
                >
                    Add a spotting
                </Link>
            </div>
        </div>

        <section class="mt-16">
            <div class="border-bark/30 flex flex-wrap items-baseline justify-between border-b pb-2">
                <h2 class="u-display text-step-2">{{ props.monthName }} — five to find</h2>
                <span class="text-step--1">
                    <Link :href="`/tree/${props.unit.id}/${props.unit.slug}/${previousMonth}`" class="underline hover:text-leaf">
                        {{ nameOfMonth(previousMonth) }}
                    </Link>
                    <span aria-hidden="true" class="text-ink-soft mx-2">|</span>
                    <Link :href="`/tree/${props.unit.id}/${props.unit.slug}/${props.nextMonth}`" class="underline hover:text-leaf">
                        {{ nameOfMonth(props.nextMonth) }}
                    </Link>
                </span>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <SpeciesCard
                    v-for="challenge in props.challenges"
                    :key="challenge.id"
                    v-bind="challenge"
                    :spotted="
                        challenge.category ? spottedCategories[challenge.category] === true : false
                    "
                />
            </div>
        </section>

        <section v-if="props.spottings.length" class="mt-16">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                Everything {{ props.unit.title }} has spotted
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="spotting in props.spottings" :key="spotting.id">
                    <SpottingPhoto v-bind="spotting" />
                    <p v-if="props.isMyGroup" class="text-step--1 mt-1">
                        <Link
                            :href="`/spottings/edit/${spotting.id}`"
                            class="underline hover:text-leaf"
                        >
                            Edit this spotting
                        </Link>
                    </p>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
