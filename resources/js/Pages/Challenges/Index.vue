<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SpeciesCard from '@/Components/SpeciesCard.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';
import SiteLayout from '@/Layouts/SiteLayout.vue';

interface Challenge {
    id: number;
    title: string;
    slug: string;
    teaser: string | null;
    image: string | null;
    month: number | null;
    category: string | null;
}

defineProps<{ past: Challenge[]; upcoming: Challenge[]; currentMonth: number }>();

// Cards fade in a row at a time as they scroll into view.
const { container: pastGrid } = useScrollReveal();
const { container: upcomingGrid } = useScrollReveal();
</script>

<template>
    <Head title="All the challenges" />

    <SiteLayout
        title="All 60 species"
        lede="Five for every month of the year. Anything from an earlier month still counts whenever you spot it."
    >
        <section>
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">Out now</h2>
            <div ref="pastGrid" class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <SpeciesCard v-for="challenge in past" :key="challenge.id" v-bind="challenge" />
            </div>
        </section>

        <section v-if="upcoming.length" class="mt-14">
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                Still to come this year
            </h2>
            <div ref="upcomingGrid" class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <SpeciesCard v-for="challenge in upcoming" :key="challenge.id" v-bind="challenge" />
            </div>
        </section>
    </SiteLayout>
</template>
