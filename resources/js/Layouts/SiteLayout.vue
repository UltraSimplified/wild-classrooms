<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import SiteFooter from '@/Components/SiteFooter.vue';
import SiteNav from '@/Components/SiteNav.vue';
import type { PageProps } from '@/types';

defineProps<{ title?: string; lede?: string }>();

const page = usePage<PageProps>();
</script>

<template>
    <div class="u-paper flex min-h-screen flex-col">
        <SiteNav />

        <div
            v-if="page.props.flash.success || page.props.flash.error"
            class="mx-auto mt-4 w-full max-w-6xl px-4"
        >
            <p
                role="status"
                class="!max-w-none border-l-4 px-4 py-3"
                :class="
                    page.props.flash.error
                        ? 'border-berry bg-berry/10 text-berry'
                        : 'border-leaf bg-leaf/10 text-ink'
                "
            >
                {{ page.props.flash.error ?? page.props.flash.success }}
            </p>
        </div>

        <main class="mx-auto w-full max-w-6xl grow px-4 py-10">
            <header v-if="title" class="mb-8">
                <h1 class="u-display text-step-3 sm:text-step-4">{{ title }}</h1>
                <p v-if="lede" class="text-step-1 text-ink-soft mt-3">{{ lede }}</p>
            </header>

            <slot />
        </main>

        <SiteFooter />
    </div>
</template>
