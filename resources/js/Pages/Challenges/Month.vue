<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const props = defineProps<{ month: number; monthName: string; challenges: Challenge[] }>();

const previous = computed(() => (props.month === 1 ? 12 : props.month - 1));
const next = computed(() => (props.month === 12 ? 1 : props.month + 1));
const nameOfMonth = (m: number) =>
    new Date(2000, m - 1, 1).toLocaleString('en-GB', { month: 'long' });

// Cards fade in a row at a time as they scroll into view.
const { container: speciesGrid } = useScrollReveal();
</script>

<template>
    <Head :title="`${props.monthName} — five to find`" />

    <SiteLayout
        :title="`${props.monthName} — five to find`"
        lede="Go and look for these. Tick off whatever you see, and add a photo or a drawing if you like."
    >
        <div ref="speciesGrid" class="mt-2 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
            <SpeciesCard
                v-for="challenge in props.challenges"
                :key="challenge.id"
                v-bind="challenge"
            />
        </div>

        <nav class="border-bark/30 mt-12 flex justify-between border-t pt-4" aria-label="Months">
            <Link :href="`/month/${previous}`" class="hover:text-leaf underline">
                {{ nameOfMonth(previous) }}
            </Link>
            <Link :href="`/month/${next}`" class="hover:text-leaf underline">
                {{ nameOfMonth(next) }}
            </Link>
        </nav>
    </SiteLayout>
</template>
