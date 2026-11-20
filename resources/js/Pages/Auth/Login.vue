<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

defineProps<{ canResetPassword?: boolean; status?: string }>();

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Log in" />

    <SiteLayout title="Log in">
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

            <FormField label="Password" for="password" :error="form.errors.password">
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <label class="flex items-center gap-2">
                <input v-model="form.remember" type="checkbox" />
                <span>Keep me signed in</span>
            </label>

            <div class="flex flex-wrap items-center gap-4">
                <button
                    type="submit"
                    class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Logging in…' : 'Log in' }}
                </button>

                <Link v-if="canResetPassword" href="/forgot-password" class="underline hover:text-leaf">
                    Forgotten your password?
                </Link>

                <Link href="/register" class="underline hover:text-leaf">Join in instead</Link>
            </div>
        </form>
    </SiteLayout>
</template>
