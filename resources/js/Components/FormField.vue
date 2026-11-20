<script setup lang="ts">
defineProps<{ label: string; error?: string; hint?: string; for?: string }>();
</script>

<template>
    <div>
        <label :for="$props.for" class="block font-bold">{{ label }}</label>
        <p v-if="hint" class="text-step--1 text-ink-soft !max-w-none">{{ hint }}</p>
        <slot />
        <p v-if="error" class="text-step--1 text-berry mt-1 font-bold !max-w-none">{{ error }}</p>
    </div>
</template>
