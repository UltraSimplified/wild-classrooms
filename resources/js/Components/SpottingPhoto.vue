<script setup lang="ts">
const props = defineProps<{
    picture?: string | null;
    notes?: string | null;
    comment?: string | null;
    challenge?: string | null;
    group?: string | null;
    spottedAt?: string | null;
}>();
</script>

<template>
    <figure class="border-bark/30 bg-parchment-deep/40 border">
        <img
            v-if="props.picture"
            :src="props.picture"
            :alt="props.challenge ?? 'A spotting'"
            class="w-full object-cover"
            loading="lazy"
        />
        <figcaption class="p-3">
            <p v-if="props.challenge" class="u-display text-step-0 !max-w-none">
                {{ props.challenge }}
            </p>
            <p v-if="props.notes" class="text-step--1 mt-1 !max-w-none">{{ props.notes }}</p>
            <p v-if="props.comment" class="text-step--1 text-leaf mt-1 italic !max-w-none">
                {{ props.comment }}
            </p>
            <p v-if="props.group || props.spottedAt" class="text-step--1 text-ink-soft mt-2 !max-w-none">
                <template v-if="props.group">{{ props.group }}</template>
                <template v-if="props.group && props.spottedAt">, </template>
                <template v-if="props.spottedAt">{{ props.spottedAt }}</template>
            </p>
        </figcaption>
    </figure>
</template>
