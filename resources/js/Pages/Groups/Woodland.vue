<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import NameBoard from '@/Components/NameBoard.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';
import SiteLayout from '@/Layouts/SiteLayout.vue';

interface Member {
    id: number;
    title: string;
    slug: string;
    spottings: number;
}

const props = defineProps<{
    group: {
        id: number;
        title: string;
        slug: string;
        type: string | null;
        description: string | null;
        image: string | null;
        city: string | null;
        county: string | null;
    };
    members: Member[];
    isMyGroup: boolean;
}>();

// Cards fade in a row at a time as they scroll into view.
const { container: woodland } = useScrollReveal();
</script>

<template>
    <Head :title="`${props.group.title} — our woodland`" />

    <SiteLayout>
        <header class="mb-8">
            <h1 class="u-display text-step-3 sm:text-step-4">{{ props.group.title }}</h1>
            <p v-if="props.group.city" class="text-ink-soft">
                {{ props.group.city
                }}<template v-if="props.group.county">, {{ props.group.county }}</template>
            </p>
            <div
                v-if="props.group.description"
                class="mt-3 max-w-prose"
                v-html="props.group.description"
            />
        </header>

        <!--
          The woodland. The old site put this in an iframe pointing at a
          separate scene route; the trees are plain images here, so each one is
          a real link a child can tab to.
        -->
        <section>
            <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                {{ props.members.length === 1 ? 'One tree' : `${props.members.length} trees` }}
            </h2>

            <ul
                ref="woodland"
                class="mt-8 flex flex-wrap items-end justify-center gap-x-2 gap-y-10"
            >
                <li
                    v-for="member in props.members"
                    :key="member.id"
                    class="min-w-40 shrink-0 text-center sm:min-w-48"
                >
                    <!--
                        The tree stands on its own ground now — grass, flowers,
                        fallen leaves or snow, depending on the season — so it
                        can sit squarely over the name board without looking as
                        if it is balanced on it. The card has a minimum width
                        rather than a fixed one, so a long name widens the card
                        instead of being cut short.
                    -->
                    <Link
                        :href="`/tree/${member.id}/${member.slug}`"
                        class="group flex flex-col items-center"
                        :aria-label="`${member.title}'s tree, ${member.spottings} spottings`"
                    >
                        <img
                            :src="`/tree.svg/${member.id}`"
                            :alt="''"
                            aria-hidden="true"
                            width="881"
                            height="1181"
                            class="block w-40 sm:w-48"
                            loading="lazy"
                        />
                        <NameBoard :name="member.title" class="mt-1" />
                        <span class="text-step--1 text-ink-soft group-hover:text-leaf mt-2 block">
                            {{ member.spottings }}
                            {{ member.spottings === 1 ? 'spotting' : 'spottings' }}
                        </span>
                    </Link>
                </li>
            </ul>

            <p v-if="!props.members.length" class="text-step-1 mt-6">
                No members yet.
                <Link v-if="props.isMyGroup" href="/profile/edit" class="hover:text-leaf underline">
                    Add some.
                </Link>
            </p>
        </section>

        <p v-if="props.isMyGroup" class="mt-12">
            <Link href="/profile/edit" class="hover:text-leaf underline">
                Edit your group and members
            </Link>
        </p>
    </SiteLayout>
</template>
