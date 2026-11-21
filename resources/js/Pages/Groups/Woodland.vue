<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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

            <ul class="mt-8 flex flex-wrap items-end justify-center gap-x-2 gap-y-10">
                <li
                    v-for="member in props.members"
                    :key="member.id"
                    class="w-40 shrink-0 text-center sm:w-48"
                >
                    <Link
                        :href="`/tree/${member.id}/${member.slug}`"
                        class="group block"
                        :aria-label="`${member.title}'s tree, ${member.spottings} spottings`"
                    >
                        <!--
                            The name board stands at ground level in front of
                            the tree, which is set over to the right and a
                            little behind it. It sits low enough that the
                            ground at the foot of the tree — fallen leaves in
                            autumn, a drift in winter — still shows. The board
                            is the name label, so the name is not repeated as
                            text below. Its offsets are percentages, which CSS
                            resolves against width, so the arrangement holds
                            at every card size.
                        -->
                        <img
                            :src="`/tree.svg/${member.id}`"
                            :alt="''"
                            aria-hidden="true"
                            class="ml-auto block w-[82%]"
                            loading="lazy"
                        />
                        <img
                            :src="`/sign.svg/${member.id}`"
                            :alt="''"
                            aria-hidden="true"
                            class="relative -mt-[3%] ml-[4%] block w-[56%]"
                            loading="lazy"
                        />
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
