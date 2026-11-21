<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import svpLogo from '@/../images/svp-logo.webp';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const svpUrl = 'https://www.stroudvalleysproject.org/';
</script>

<template>
    <Head title="About Wild Classrooms" />

    <SiteLayout title="About Wild Classrooms">
        <div class="grid gap-10 md:grid-cols-3">
            <div class="space-y-4 md:col-span-2">
                <p>
                    Wild Classrooms supports outdoor learning in Gloucestershire schools. It is
                    built around getting children outside to watch the seasons change, and it covers
                    parts of the science curriculum — adaptation, classification, plant
                    reproduction. It adapts just as easily to a sensory approach to nature, or as a
                    starting point for creative work.
                </p>

                <p>
                    Every participating class gets a free A2 colour wildlife poster each month, a
                    class notebook, teacher's notes, and a visit from our DBS-checked project
                    officer. There are also six themed calendars covering local birds, butterflies
                    and moths, trees, invertebrates, wildflowers, and Cotswold rural skills.
                </p>

                <p>
                    The project is flexible, so teachers can use as much or as little of it as suits
                    them.
                </p>

                <h2 class="u-display text-step-2 border-bark/30 mt-10 border-b pb-2">
                    About Stroud Valleys Project
                </h2>

                <div class="flex flex-col items-start gap-5 sm:flex-row">
                    <!--
                        The logo is drawn on white, so it sits on a white tile
                        rather than straight on the parchment.

                        It links to the charity, and so does its name at the
                        start of the paragraph beside it. Two links side by
                        side to the same place would be announced twice by a
                        screen reader and cost a keyboard user an extra stop,
                        so the name is the link that counts and the logo is
                        one for the mouse: out of the tab order and hidden
                        from assistive technology, with an empty alt.
                    -->
                    <a
                        :href="svpUrl"
                        target="_blank"
                        rel="noopener"
                        tabindex="-1"
                        aria-hidden="true"
                        class="svp-logo block w-32 shrink-0 sm:w-36"
                    >
                        <div class="svp-logo__tile">
                            <img :src="svpLogo" alt="" width="646" height="558" />
                        </div>
                    </a>

                    <div class="space-y-4">
                        <p>
                            <a
                                :href="svpUrl"
                                target="_blank"
                                rel="noopener"
                                class="hover:text-leaf underline"
                                >Stroud Valleys Project</a
                            >
                            is a small Gloucestershire environmental charity, founded in 1988 to
                            help protect the industrial heritage of Stroud and its five valleys.
                            Since then we have branched out into the natural environment, and we
                            have an established reputation for work with local community groups and
                            volunteers — helping, inspiring and training them to discover and
                            nurture the wildlife and green spaces on their doorsteps.
                        </p>

                        <p>
                            Wild Classrooms is our educational project, and this site supports it.
                        </p>
                    </div>
                </div>
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
                        class="hover:text-leaf underline"
                    >
                        tamsin@stroudvalleysproject.org
                    </a>
                    <br />
                    <a href="tel:+441453753358" class="hover:text-leaf underline">01453 753358</a>
                </p>
            </aside>
        </div>
    </SiteLayout>
</template>

<style scoped>
/*
 * A squircle frame. CSS can draw one directly with corner-shape, but only
 * Chromium supports that, so Safari — and the iPads many schools use — would
 * get an ordinary rounded rectangle. A mask works everywhere.
 *
 * The shape is a superellipse (resources/svg/squircle.svg), stretched to the
 * box. The outer layer is the frame's colour, the site's usual hairline; the
 * white tile sits 1px inside it, so the 1px that shows round the edge is the
 * border. The tile's padding is a percentage, so it scales with the logo and
 * keeps the lettering clear of the curved corners.
 */
.svp-logo,
.svp-logo__tile {
    -webkit-mask: url('../../../svg/squircle.svg') center / 100% 100% no-repeat;
    mask: url('../../../svg/squircle.svg') center / 100% 100% no-repeat;
}

.svp-logo {
    padding: 1px;
    background: rgb(143 120 92 / 0.35);
    transition: background-color 150ms;
}

.svp-logo:hover {
    background: rgb(143 120 92 / 0.8);
}

.svp-logo__tile {
    padding: 13%;
    background: #fff;
}

.svp-logo img {
    display: block;
    width: 100%;
    height: auto;
}
</style>
