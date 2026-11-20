<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const props = defineProps<{
    user: { id: number; name: string; email: string; roleIds: number[] };
    roles: { id: number; name: string }[];
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    roles: [...props.user.roleIds],
});

const submit = () => form.put(`/admin/users/${props.user.id}`);
</script>

<template>
    <Head :title="`Edit ${props.user.name}`" />

    <SiteLayout :title="`Edit ${props.user.name}`">
        <form class="max-w-xl space-y-6" @submit.prevent="submit">
            <FormField label="Name" for="name" :error="form.errors.name">
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField label="Email" for="email" :error="form.errors.email">
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <fieldset>
                <legend class="font-bold">Roles</legend>
                <label
                    v-for="role in props.roles"
                    :key="role.id"
                    class="mt-2 flex items-center gap-2"
                >
                    <input v-model="form.roles" type="checkbox" :value="role.id" />
                    <span>{{ role.name }}</span>
                </label>
            </fieldset>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>

                <Link href="/admin/users" class="underline hover:text-leaf">Cancel</Link>
            </div>
        </form>
    </SiteLayout>
</template>
