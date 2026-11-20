<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submit = () =>
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
</script>

<template>
    <Head title="Join in" />

    <SiteLayout
        title="Join in"
        lede="Set up a free account, then create your group and plant your woodland."
    >
        <form class="max-w-md space-y-5" @submit.prevent="submit">
            <FormField label="Your name" for="name" :error="form.errors.name">
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    required
                    autofocus
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

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
                label="Password"
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
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField
                label="Password again"
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

            <p class="text-step--1 text-ink-soft">
                By joining you agree to our
                <Link href="/terms-conditions" class="underline hover:text-leaf">
                    terms and conditions</Link>.
            </p>

            <div class="flex flex-wrap items-center gap-4">
                <button
                    type="submit"
                    class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Creating…' : 'Create my account' }}
                </button>

                <Link href="/login" class="underline hover:text-leaf">
                    I already have an account
                </Link>
            </div>
        </form>
    </SiteLayout>
</template>
