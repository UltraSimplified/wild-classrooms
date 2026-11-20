<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const form = useForm({ password: '' });

const submit = () => form.post('/confirm-password', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Confirm your password" />

    <SiteLayout title="Confirm your password" lede="Just checking it is you before you carry on.">
        <form class="max-w-md space-y-5" @submit.prevent="submit">
            <FormField label="Password" for="password" :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
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
                Confirm
            </button>
        </form>
    </SiteLayout>
</template>
