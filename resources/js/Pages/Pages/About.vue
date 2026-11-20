<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import SiteLayout from '@/Layouts/SiteLayout.vue';
</script>

<template>
    <Head title="About Wild Classrooms" />

    <SiteLayout title="About Wild Classrooms">
        <div class="grid gap-10 md:grid-cols-3">
            <div class="space-y-4 md:col-span-2">
                <p>
                    Wild Classrooms supports outdoor learning in Gloucestershire schools. It is
                    built around getting children outside to watch the seasons change, and it
                    covers parts of the science curriculum — adaptation, classification, plant
                    reproduction. It adapts just as easily to a sensory approach to nature, or as
                    a starting point for creative work.
                </p>

                <p>
                    Every participating class gets a free A2 colour wildlife poster each month, a
                    class notebook, teacher's notes, and a visit from our DBS-checked project
                    officer. There are also six themed calendars covering local birds, butterflies
                    and moths, trees, invertebrates, wildflowers, and Cotswold rural skills.
                </p>

                <p>
                    The project is flexible, so teachers can use as much or as little of it as
                    suits them.
                </p>

                <h2 class="u-display text-step-2 border-bark/30 mt-10 border-b pb-2">
                    About Stroud Valleys Project
                </h2>

                <p>
                    Stroud Valleys Project is a small Gloucestershire environmental charity,
                    founded in 1988 to help protect the industrial heritage of Stroud and its five
                    valleys. Since then we have branched out into the natural environment, and we
                    have an established reputation for work with local community groups and
                    volunteers — helping, inspiring and training them to discover and nurture the
                    wildlife and green spaces on their doorsteps.
                </p>

                <p>
                    Wild Classrooms is our educational project, and this site supports it.
                </p>
            </div>

            <aside class="border-bark/30 bg-parchment-deep/40 border p-5 md:col-span-1">
                <h2 class="u-display text-step-1">Get in touch</h2>
                <p class="mt-2 !max-w-none">
                    Tamsin Bent<br />
                    Wild Classrooms Project Officer
                </p>
                <p class="mt-3 !max-w-none">
                    <a
                        href="mailto:tamsin@stroudvalleysproject.org"
                        class="underline hover:text-leaf"
                    >
                        tamsin@stroudvalleysproject.org
                    </a>
                    <br />
                    <a href="tel:+441453753358" class="underline hover:text-leaf">01453 753358</a>
                </p>
            </aside>
        </div>
    </SiteLayout>
</template>
