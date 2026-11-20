<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    joined: string | null;
}

const props = defineProps<{
    users: { data: AdminUser[]; links: { url: string | null; label: string; active: boolean }[] };
}>();

const destroy = (user: AdminUser) => {
    if (confirm(`Delete ${user.name}? This cannot be undone.`)) {
        router.delete(`/admin/users/${user.id}`);
    }
};
</script>

<template>
    <Head title="Users" />

    <SiteLayout title="Users">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="border-bark/40 border-b-2">
                    <th scope="col" class="py-2 pr-4">Name</th>
                    <th scope="col" class="py-2 pr-4">Email</th>
                    <th scope="col" class="py-2 pr-4">Roles</th>
                    <th scope="col" class="py-2 pr-4">Joined</th>
                    <th scope="col" class="py-2"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in props.users.data" :key="user.id" class="border-bark/20 border-b">
                    <td class="py-2 pr-4">{{ user.name }}</td>
                    <td class="py-2 pr-4">{{ user.email }}</td>
                    <td class="py-2 pr-4">{{ user.roles.join(', ') || '—' }}</td>
                    <td class="py-2 pr-4">{{ user.joined }}</td>
                    <td class="py-2 text-right whitespace-nowrap">
                        <Link
                            :href="`/admin/users/${user.id}/edit`"
                            class="underline hover:text-leaf"
                        >
                            Edit
                        </Link>
                        <button type="button" class="text-berry ml-3 underline" @click="destroy(user)">
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <nav v-if="props.users.links.length > 3" class="mt-6 flex flex-wrap gap-2" aria-label="Pages">
            <Link
                v-for="link in props.users.links"
                :key="link.label"
                :href="link.url ?? ''"
                class="border-bark/30 border px-3 py-1"
                :class="link.active ? 'bg-leaf text-parchment' : 'hover:border-leaf'"
                :aria-disabled="link.url === null"
            >
                <!-- Laravel writes these labels with HTML entities. -->
                <span v-html="link.label" />
            </Link>
        </nav>
    </SiteLayout>
</template>
