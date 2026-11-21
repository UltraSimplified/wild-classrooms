<script setup lang="ts">
import MediaImage from '@/Components/MediaImage.vue';
import type { ImageSize } from '@/types';

const props = defineProps<{
    picture?: string | null;
    size?: ImageSize | null;
    notes?: string | null;
    comment?: string | null;
    challenge?: string | null;
    group?: string | null;
    spottedAt?: string | null;
}>();
</script>

<template>
    <figure class="border-bark/30 bg-parchment-deep/40 border">
        <MediaImage
            :src="props.picture"
            :size="props.size"
            :alt="props.challenge ?? 'A spotting'"
            class="w-full object-cover"
        />
        <figcaption class="p-3">
            <p v-if="props.challenge" class="u-display text-step-0 !max-w-none">
                {{ props.challenge }}
            </p>
            <p v-if="props.notes" class="text-step--1 mt-1 !max-w-none">{{ props.notes }}</p>
            <p v-if="props.comment" class="text-step--1 text-leaf mt-1 !max-w-none italic">
                {{ props.comment }}
            </p>
            <p
                v-if="props.group || props.spottedAt"
                class="text-step--1 text-ink-soft mt-2 !max-w-none"
            >
                <template v-if="props.group">{{ props.group }}</template>
                <template v-if="props.group && props.spottedAt">, </template>
                <template v-if="props.spottedAt">{{ props.spottedAt }}</template>
            </p>
        </figcaption>
    </figure>
</template>
