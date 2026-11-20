<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';

defineProps<{
    posts: {
        id: number;
        title: string;
        slug: string;
        intro: string | null;
        image: string | null;
        postedAt: string | null;
        category: string | null;
        author: string | null;
    }[];
}>();
</script>

<template>
    <Head title="Dig deeper" />

    <SiteLayout title="Dig deeper" lede="Longer reads about the wildlife on the challenges.">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="post in posts" :key="post.id" class="border-bark/30 border">
                <Link :href="`/dig-deeper/view/${post.id}/${post.slug}`">
                    <img
                        v-if="post.image"
                        :src="post.image"
                        :alt="post.title"
                        class="aspect-16/9 w-full object-cover"
                        loading="lazy"
                    />
                </Link>
                <div class="p-4">
                    <h2 class="u-display text-step-1">
                        <Link
                            :href="`/dig-deeper/view/${post.id}/${post.slug}`"
                            class="hover:text-leaf"
                        >
                            {{ post.title }}
                        </Link>
                    </h2>
                    <p class="text-step--1 text-ink-soft !max-w-none">
                        {{ post.postedAt }}
                    </p>
                    <div v-if="post.intro" class="text-step--1 mt-2" v-html="post.intro" />
                </div>
            </article>
        </div>

        <p v-if="!posts.length" class="text-step-1">No posts yet.</p>
    </SiteLayout>
</template>
