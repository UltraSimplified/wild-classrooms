<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

defineProps<{ status?: string }>();

const form = useForm({ email: '' });

const submit = () => form.post('/forgot-password');
</script>

<template>
    <Head title="Forgotten password" />

    <SiteLayout
        title="Forgotten your password?"
        lede="Give us your email address and we will send you a link to set a new one."
    >
        <p v-if="status" role="status" class="border-leaf bg-leaf/10 mb-6 border-l-4 px-4 py-3">
            {{ status }}
        </p>

        <form class="max-w-md space-y-5" @submit.prevent="submit">
            <FormField label="Email" for="email" :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    autofocus
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <button
                type="submit"
                class="bg-berry hover:bg-berry-bright text-parchment px-5 py-3 font-bold disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Sending…' : 'Send the link' }}
            </button>
        </form>
    </SiteLayout>
</template>
