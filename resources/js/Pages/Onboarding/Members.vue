<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';

const props = defineProps<{ group: { id: number; title: string } }>();

/*
 * Ten slots, as the welcome email promises. Each member gets their own tree, so
 * these are the names that appear on the signposts in the woodland.
 */
const form = useForm({
    members: Array.from({ length: 10 }, () => ''),
});

const submit = () => form.post('/profiles/onboarding-members');
</script>

<template>
    <Head title="Add your members" />

    <SiteLayout
        :title="`Who is in ${props.group.title}?`"
        lede="Each member gets their own tree. Add up to ten — a child, a class, a six, a whole family. You can change these later."
    >
        <form class="max-w-xl space-y-4" @submit.prevent="submit">
            <div v-for="(_, index) in form.members" :key="index">
                <label :for="`member-${index}`" class="sr-only"> Member {{ index + 1 }} </label>
                <input
                    :id="`member-${index}`"
                    v-model="form.members[index]"
                    type="text"
                    :placeholder="index === 0 ? 'First member' : 'Another member'"
                    class="border-bark/40 bg-parchment w-full border"
                />
            </div>

            <p v-if="form.errors.members" class="text-berry font-bold">
                {{ form.errors.members }}
            </p>

            <button
                type="submit"
                class="bg-berry hover:bg-berry-bright text-parchment px-5 py-3 font-bold disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Planting…' : 'Plant our woodland' }}
            </button>
        </form>
    </SiteLayout>
</template>
