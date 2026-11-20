<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { PageProps } from '@/types';

defineProps<{ status?: string }>();

const page = usePage<PageProps>();

const details = useForm({
    name: page.props.auth.user?.name ?? '',
    email: page.props.auth.user?.email ?? '',
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deletion = useForm({ password: '' });

const updateDetails = () => details.patch('/profile');

const updatePassword = () =>
    password.put('/password', {
        preserveScroll: true,
        onSuccess: () => password.reset(),
    });

const deleteAccount = () => {
    if (
        confirm(
            'Delete your account? Your group, its members and all their spottings go too, and this cannot be undone.',
        )
    ) {
        deletion.delete('/profile', { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Your account" />

    <SiteLayout title="Your account">
        <p v-if="status" role="status" class="border-leaf bg-leaf/10 mb-6 border-l-4 px-4 py-3">
            {{ status }}
        </p>

        <div class="max-w-xl space-y-12">
            <section>
                <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">Your details</h2>

                <form class="mt-5 space-y-5" @submit.prevent="updateDetails">
                    <FormField label="Name" for="name" :error="details.errors.name">
                        <input
                            id="name"
                            v-model="details.name"
                            type="text"
                            required
                            class="border-bark/40 bg-parchment mt-1 w-full border"
                        />
                    </FormField>

                    <FormField label="Email" for="email" :error="details.errors.email">
                        <input
                            id="email"
                            v-model="details.email"
                            type="email"
                            required
                            class="border-bark/40 bg-parchment mt-1 w-full border"
                        />
                    </FormField>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            class="bg-berry hover:bg-berry-bright text-parchment px-5 py-3 font-bold disabled:opacity-60"
                            :disabled="details.processing"
                        >
                            Save details
                        </button>
                        <span v-if="details.recentlySuccessful" class="text-leaf">Saved</span>
                    </div>
                </form>

                <p class="mt-4">
                    <Link href="/profile/edit" class="hover:text-leaf underline">
                        Edit your group and members instead
                    </Link>
                </p>
            </section>

            <section>
                <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">Password</h2>

                <form class="mt-5 space-y-5" @submit.prevent="updatePassword">
                    <FormField
                        label="Current password"
                        for="current_password"
                        :error="password.errors.current_password"
                    >
                        <input
                            id="current_password"
                            v-model="password.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="border-bark/40 bg-parchment mt-1 w-full border"
                        />
                    </FormField>

                    <FormField
                        label="New password"
                        for="new_password"
                        hint="At least eight characters."
                        :error="password.errors.password"
                    >
                        <input
                            id="new_password"
                            v-model="password.password"
                            type="password"
                            autocomplete="new-password"
                            class="border-bark/40 bg-parchment mt-1 w-full border"
                        />
                    </FormField>

                    <FormField
                        label="New password again"
                        for="new_password_confirmation"
                        :error="password.errors.password_confirmation"
                    >
                        <input
                            id="new_password_confirmation"
                            v-model="password.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="border-bark/40 bg-parchment mt-1 w-full border"
                        />
                    </FormField>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            class="bg-berry hover:bg-berry-bright text-parchment px-5 py-3 font-bold disabled:opacity-60"
                            :disabled="password.processing"
                        >
                            Change password
                        </button>
                        <span v-if="password.recentlySuccessful" class="text-leaf">Changed</span>
                    </div>
                </form>
            </section>

            <section>
                <h2 class="u-display text-step-2 border-bark/30 border-b pb-2">
                    Delete your account
                </h2>

                <p class="mt-3">
                    This removes your group, its members and everything they have spotted. We cannot
                    get any of it back afterwards.
                </p>

                <form class="mt-5 space-y-5" @submit.prevent="deleteAccount">
                    <FormField
                        label="Confirm your password"
                        for="delete_password"
                        :error="deletion.errors.password"
                    >
                        <input
                            id="delete_password"
                            v-model="deletion.password"
                            type="password"
                            autocomplete="current-password"
                            class="border-bark/40 bg-parchment mt-1 w-full max-w-xs border"
                        />
                    </FormField>

                    <button
                        type="submit"
                        class="border-berry text-berry hover:bg-berry hover:text-parchment border px-5 py-3 font-bold disabled:opacity-60"
                        :disabled="deletion.processing"
                    >
                        Delete my account
                    </button>
                </form>
            </section>
        </div>
    </SiteLayout>
</template>
