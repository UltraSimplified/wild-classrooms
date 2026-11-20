<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import MediaImage from '@/Components/MediaImage.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const props = defineProps<{
    post: {
        id: number;
        title: string;
        intro: string | null;
        content: string | null;
        image: string | null;
        postedAt: string | null;
        category: string | null;
        author: string | null;
        challenge: { id: number; title: string; slug: string } | null;
    };
}>();
</script>

<template>
    <Head :title="props.post.title" />

    <SiteLayout>
        <article class="mx-auto max-w-2xl">
            <h1 class="u-display text-step-3 sm:text-step-4">{{ props.post.title }}</h1>
            <p class="text-ink-soft mt-2">
                {{ props.post.postedAt }}<template v-if="props.post.author">, by {{ props.post.author }}</template>
            </p>

            <MediaImage
                :src="props.post.image"
                :alt="props.post.title"
                class="border-bark/30 mt-6 w-full border"
                loading="eager"
            />

            <div v-if="props.post.intro" class="text-step-1 prose-wc mt-6" v-html="props.post.intro" />
            <div v-if="props.post.content" class="prose-wc mt-4" v-html="props.post.content" />

            <p v-if="props.post.challenge" class="border-bark/30 mt-10 border-t pt-4">
                <Link
                    :href="`/spottings/${props.post.challenge.id}/${props.post.challenge.slug}`"
                    class="underline hover:text-leaf"
                >
                    See the {{ props.post.challenge.title }} challenge
                </Link>
            </p>
        </article>
    </SiteLayout>
</template>

<style scoped>
.prose-wc :deep(p) {
    margin-block: 0.85em;
    max-width: 64ch;
}
.prose-wc :deep(a) {
    text-decoration: underline;
}
.prose-wc :deep(h2),
.prose-wc :deep(h3) {
    font-family: var(--font-display);
    font-size: var(--text-step-2);
    line-height: 1.15;
    margin-block: 1.4em 0.4em;
}
.prose-wc :deep(ul) {
    list-style: disc;
    padding-left: 1.25rem;
    margin-block: 0.85em;
}
.prose-wc :deep(img) {
    margin-block: 1em;
}
</style>
