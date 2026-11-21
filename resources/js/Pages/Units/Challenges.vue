<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import CategoryProgress from '@/Components/CategoryProgress.vue';
import SpeciesCard from '@/Components/SpeciesCard.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const props = defineProps<{
    unit: { id: number; title: string; slug: string };
    group: { id: number | null; title: string | null; slug: string };
    month: number;
    monthName: string;
    challenges: {
        id: number;
        title: string;
        slug: string;
        teaser: string | null;
        image: string | null;
        category: string | null;
    }[];
    spotted: Record<string, Record<string, boolean>>;
    isMyGroup: boolean;
}>();

const abbreviation = computed(() =>
    new Date(2000, props.month - 1, 1).toLocaleString('en-GB', { month: 'short' }),
);

const spottedCategories = computed(() => props.spotted[abbreviation.value] ?? {});
</script>

<template>
    <Head :title="`${props.unit.title} — ${props.monthName}`" />

    <SiteLayout :title="`${props.unit.title} — ${props.monthName}`">
        <div class="grid gap-8 md:grid-cols-4">
            <div class="md:col-span-1">
                <CategoryProgress
                    :month-abbreviation="abbreviation"
                    :month-name="props.monthName"
                    :spotted="props.spotted"
                />
                <p class="mt-4">
                    <Link
                        :href="`/tree/${props.unit.id}/${props.unit.slug}`"
                        class="hover:text-leaf underline"
                    >
                        See the tree
                    </Link>
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 md:col-span-3 lg:grid-cols-3">
                <SpeciesCard
                    v-for="challenge in props.challenges"
                    :key="challenge.id"
                    v-bind="challenge"
                    :spotted="
                        challenge.category ? spottedCategories[challenge.category] === true : false
                    "
                />
            </div>
        </div>
    </SiteLayout>
</template>
