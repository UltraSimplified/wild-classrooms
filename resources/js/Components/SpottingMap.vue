<script setup lang="ts">
import { circleMarker, latLngBounds, map as createMap, tileLayer } from 'leaflet';
import type { LatLngTuple, Map as LeafletMap } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref } from 'vue';

import type { MapPoint } from '@/types';

/*
 * Spottings on an OpenStreetMap basemap.
 *
 * The old site used Google Maps with the API key pasted into three Blade
 * templates. Leaflet needs neither a key nor a billing account, and the tiles
 * cost nothing beyond the attribution the control shows.
 *
 * Markers are circles rather than pins, which keeps Leaflet's default marker
 * images out of the bundle — their paths break under Vite unless rewritten —
 * and lets the markers take their colour from the site's own palette.
 */
const props = withDefaults(defineProps<{ points: MapPoint[]; label?: string }>(), {
    label: 'Map of recorded spottings',
});

const container = ref<HTMLDivElement | null>(null);
let map: LeafletMap | null = null;

/** Read a colour from the theme rather than restating the palette here. */
const themeColour = (name: string, fallback: string): string =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

/*
 * Built as DOM nodes rather than an HTML string: notes are written by children
 * and go in as text, so there is no way for markup to be interpreted.
 */
const popupFor = (point: MapPoint): HTMLElement => {
    const wrap = document.createElement('div');

    if (point.picture) {
        const photo = document.createElement('img');
        photo.src = point.picture;
        photo.alt = '';
        photo.className = 'wc-popup-photo';
        wrap.append(photo);
    }

    if (point.notes) {
        const notes = document.createElement('p');
        notes.textContent = point.notes;
        wrap.append(notes);
    }

    if (point.spottedAt) {
        const date = document.createElement('p');
        date.className = 'wc-popup-date';
        date.textContent = `Spotted on ${point.spottedAt}`;
        wrap.append(date);
    }

    if (wrap.childElementCount === 0) {
        wrap.textContent = 'A spotting';
    }

    return wrap;
};

onMounted((): void => {
    if (container.value === null || props.points.length === 0) {
        return;
    }

    // A map sitting in a page of text should not swallow the page scroll.
    map = createMap(container.value, { scrollWheelZoom: false });

    tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    const fillColor = themeColour('--color-berry', '#8c2f39');
    const color = themeColour('--color-parchment', '#f2ead6');

    for (const point of props.points) {
        circleMarker([point.latitude, point.longitude], {
            radius: 7,
            weight: 2,
            color,
            fillColor,
            fillOpacity: 0.9,
        })
            .addTo(map)
            .bindPopup(popupFor(point));
    }

    map.fitBounds(
        latLngBounds(props.points.map((point): LatLngTuple => [point.latitude, point.longitude])),
        // Without a ceiling, a single marker zooms in on one rooftop.
        { padding: [30, 30], maxZoom: 15 },
    );
});

onBeforeUnmount((): void => {
    map?.remove();
    map = null;
});
</script>

<template>
    <div
        ref="container"
        role="region"
        :aria-label="props.label"
        class="border-bark/30 h-[320px] w-full border sm:h-[420px]"
    />
</template>

<style scoped>
/*
 * Leaflet's chrome is white with rounded corners; this site is parchment with
 * square ones. Only the parts that actually show are restyled.
 */
:deep(.leaflet-popup-content-wrapper),
:deep(.leaflet-popup-tip) {
    background: var(--color-parchment);
    color: var(--color-ink);
    border-radius: 0;
}

:deep(.leaflet-popup-content) {
    margin: 0.75rem;
    font-size: var(--text-step--1);
}

:deep(.wc-popup-photo) {
    display: block;
    width: 100%;
    max-width: 14rem;
    height: auto;
    margin-bottom: 0.5rem;
}

:deep(.wc-popup-date) {
    color: var(--color-ink-soft);
}

:deep(.leaflet-control-attribution) {
    background: rgb(242 234 214 / 0.85);
}
</style>
