<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const props = defineProps<{ email: string; token: string }>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () =>
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
</script>

<template>
    <Head title="Set a new password" />

    <SiteLayout title="Set a new password">
        <form class="max-w-md space-y-5" @submit.prevent="submit">
            <FormField label="Email" for="email" :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField
                label="New password"
                for="password"
                hint="At least eight characters."
                :error="form.errors.password"
            >
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    autofocus
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField
                label="New password again"
                for="password_confirmation"
                :error="form.errors.password_confirmation"
            >
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <button
                type="submit"
                class="bg-berry hover:bg-berry-bright text-parchment px-5 py-3 font-bold disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Saving…' : 'Save new password' }}
            </button>
        </form>
    </SiteLayout>
</template>
