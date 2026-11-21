<script setup lang="ts">
/*
 * A member's name painted on a wooden plank.
 *
 * The plank used to be an SVG drawn by the server with the name inside it,
 * which had two problems. An SVG shown through <img> cannot load the page's
 * web fonts, so the name never appeared in IM Fell English. And the server
 * cannot measure text in a font it does not have, so the sign could only fit
 * one word, and every board in a group of "Class 1" to "Class 6" said "Class".
 *
 * Here the name is real text and the plank is stretched behind it with
 * border-image. The two sawn ends, the outer 14 units of the 99-unit artwork,
 * keep their shape, and only the plain middle stretches. A long name gets a
 * longer plank rather than smaller letters.
 */
defineProps<{ name: string }>();
</script>

<template>
    <span class="name-board u-display">{{ name }}</span>
</template>

<style scoped>
.name-board {
    display: inline-block;
    white-space: nowrap;
    color: #fff;
    font-size: 0.9375rem;
    /* The artwork is 99 x 26.7; an end cap is 14 of those 99 units wide, so
       at a height of 1.8em each cap is 1.8 x 14 / 26.7 = 0.94em. */
    height: 1.8em;
    line-height: 1.85em;
    padding-inline: 0.1em;
    border-style: solid;
    border-width: 0 0.94em;
    border-image: url('../../svg/plank.svg') 0 14 fill / 0 0.94em stretch;
}
</style>
