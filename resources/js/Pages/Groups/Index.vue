<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';

defineProps<{
    groups: {
        id: number;
        title: string;
        slug: string;
        type: string | null;
        image: string | null;
        members: number;
    }[];
}>();
</script>

<template>
    <Head title="Groups" />

    <SiteLayout title="Groups taking part" lede="Every group has a woodland. Have a look round.">
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <li
                v-for="group in groups"
                :key="group.id"
                class="border-bark/30 bg-parchment-deep/40 border p-4"
            >
                <h2 class="u-display text-step-1">
                    <Link
                        :href="`/groups/woodland/${group.id}/${group.slug}`"
                        class="hover:text-leaf"
                    >
                        {{ group.title }}
                    </Link>
                </h2>
                <p class="text-step--1 text-ink-soft !max-w-none">
                    {{ group.type }}, {{ group.members }}
                    {{ group.members === 1 ? 'member' : 'members' }}
                </p>
            </li>
        </ul>
    </SiteLayout>
</template>
