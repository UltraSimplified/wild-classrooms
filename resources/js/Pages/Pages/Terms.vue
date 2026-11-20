<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';
</script>

<template>
    <Head title="Terms and conditions" />

    <SiteLayout title="Terms and conditions">
        <div class="space-y-4">
            <p>
                Wild Classrooms will use the information you supply, and any images you upload and
                choose to share publicly, as part of this project and its galleries.
            </p>

            <p>
                By registering for a free account you agree to let us email you occasionally with
                news about the project, along with messages your account needs — a password reset,
                for example.
            </p>

            <p>
                If you want to delete your account, or you would like a copy of everything you have
                uploaded, contact Tamsin Bent and we will sort it out.
            </p>

            <p class="mt-6">
                Tamsin Bent, Wild Classrooms Project Officer<br />
                <a href="mailto:tamsin@stroudvalleysproject.org" class="hover:text-leaf underline">
                    tamsin@stroudvalleysproject.org
                </a>
                <br />
                <a href="tel:+441453753358" class="hover:text-leaf underline">01453 753358</a>
            </p>
        </div>
    </SiteLayout>
</template>
