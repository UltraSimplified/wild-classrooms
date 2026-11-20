<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const open = ref(false);
</script>

<template>
    <header class="u-timber border-b-2 border-leaf-deep/40">
        <nav class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3" aria-label="Main">
            <Link
                href="/"
                class="u-display text-parchment text-step-2 shrink-0 leading-none hover:text-leaf-pale"
            >
                Wild Classrooms
            </Link>

            <button
                type="button"
                class="ml-auto rounded px-3 py-2 text-parchment md:hidden"
                :aria-expanded="open"
                aria-controls="site-menu"
                @click="open = !open"
            >
                {{ open ? 'Close' : 'Menu' }}
            </button>

            <ul
                id="site-menu"
                class="text-parchment ml-auto hidden items-center gap-5 md:flex"
            >
                <li><Link href="/about" class="hover:text-leaf-pale">About</Link></li>
                <li><Link href="/gallery" class="hover:text-leaf-pale">Gallery</Link></li>
                <li>
                    <Link
                        :href="`/month/${page.props.currentMonth.number}`"
                        class="hover:text-leaf-pale"
                    >
                        {{ page.props.currentMonth.name }}
                    </Link>
                </li>
                <li><Link href="/dig-deeper" class="hover:text-leaf-pale">Dig deeper</Link></li>

                <li v-if="page.props.auth.group">
                    <Link
                        :href="`/groups/woodland/${page.props.auth.group.id}/${page.props.auth.group.slug}`"
                        class="hover:text-leaf-pale"
                    >
                        Our woodland
                    </Link>
                </li>
                <li v-if="page.props.auth.user">
                    <Link href="/spottings/add" class="hover:text-leaf-pale">Add a spotting</Link>
                </li>
                <li v-if="page.props.auth.user">
                    <Link href="/profile/edit" class="hover:text-leaf-pale">Edit group</Link>
                </li>
                <li v-if="page.props.auth.user?.isAdmin">
                    <Link href="/admin/users" class="hover:text-leaf-pale">Users</Link>
                </li>
                <li v-if="page.props.auth.user">
                    <Link href="/logout" method="post" as="button" class="hover:text-leaf-pale">
                        Log out
                    </Link>
                </li>
                <template v-else>
                    <li><Link href="/login" class="hover:text-leaf-pale">Log in</Link></li>
                    <li>
                        <Link
                            href="/register"
                            class="bg-berry hover:bg-berry-bright rounded-sm px-3 py-1.5 font-bold text-parchment"
                        >
                            Join in
                        </Link>
                    </li>
                </template>
            </ul>
        </nav>

        <ul v-if="open" class="text-parchment border-t border-parchment/20 px-4 pb-4 md:hidden">
            <li class="py-2"><Link href="/about">About</Link></li>
            <li class="py-2"><Link href="/gallery">Gallery</Link></li>
            <li class="py-2">
                <Link :href="`/month/${page.props.currentMonth.number}`">
                    {{ page.props.currentMonth.name }}
                </Link>
            </li>
            <li class="py-2"><Link href="/dig-deeper">Dig deeper</Link></li>
            <li v-if="page.props.auth.group" class="py-2">
                <Link
                    :href="`/groups/woodland/${page.props.auth.group.id}/${page.props.auth.group.slug}`"
                >
                    Our woodland
                </Link>
            </li>
            <li v-if="page.props.auth.user" class="py-2">
                <Link href="/spottings/add">Add a spotting</Link>
            </li>
            <li v-if="page.props.auth.user" class="py-2">
                <Link href="/profile/edit">Edit group</Link>
            </li>
            <li v-if="page.props.auth.user?.isAdmin" class="py-2">
                <Link href="/admin/users">Users</Link>
            </li>
            <li v-if="page.props.auth.user" class="py-2">
                <Link href="/logout" method="post" as="button">Log out</Link>
            </li>
            <template v-else>
                <li class="py-2"><Link href="/login">Log in</Link></li>
                <li class="py-2"><Link href="/register">Join in</Link></li>
            </template>
        </ul>
    </header>
</template>
