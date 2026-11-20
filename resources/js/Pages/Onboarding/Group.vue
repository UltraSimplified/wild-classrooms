<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import GroupFields from '@/Components/GroupFields.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const form = useForm({
    title: '',
    type: 'Group',
    description: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    county: '',
    postal_code: '',
    public: true,
    picture: null as File | null,
});

const submit = () => form.post('/profiles/onboarding', { forceFormData: true });
</script>

<template>
    <Head title="Set up your group" />

    <SiteLayout
        title="Set up your group"
        lede="First, tell us who you are. You add the members on the next step."
    >
        <form class="max-w-2xl space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
            <GroupFields v-model="form" :errors="form.errors" />

            <button
                type="submit"
                class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving…' : 'Next: add members' }}
            </button>
        </form>
    </SiteLayout>
</template>
