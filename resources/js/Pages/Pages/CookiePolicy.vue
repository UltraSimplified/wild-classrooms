<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';
</script>

<template>
    <Head title="Cookie policy" />

    <SiteLayout title="Cookie policy">
        <div class="space-y-4">
            <p>
                Wild Classrooms uses a small number of cookies, and only the ones it needs: they
                keep you signed in to your group pages and let you upload spottings.
            </p>

            <p>
                We do not use advertising or tracking cookies. The old version of this page embedded
                a third-party cookie-reporting script, which has been removed — it loaded a script
                from another company on a page children visit, which is exactly the sort of thing a
                cookie policy should be reassuring you about.
            </p>
        </div>
    </SiteLayout>
</template>
