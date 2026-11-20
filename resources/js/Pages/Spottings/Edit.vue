<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import FormField from '@/Components/FormField.vue';
import MediaImage from '@/Components/MediaImage.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

const props = defineProps<{
    spotting: {
        id: number;
        unitId: number;
        challengeId: number;
        notes: string | null;
        location: string | null;
        what3words: string | null;
        picture: string | null;
        gallery: boolean;
        map: boolean;
    };
    challenges: { id: number; title: string; month: number | null; category: string | null }[];
}>();

const form = useForm({
    challenge_id: props.spotting.challengeId,
    notes: props.spotting.notes ?? '',
    location: props.spotting.location ?? '',
    what3words: props.spotting.what3words ?? '',
    gallery: props.spotting.gallery,
    map: props.spotting.map,
    picture: null as File | null,
    remove_picture: false,
});

const grouped = computed(() => {
    const byMonth = new Map<string, typeof props.challenges>();

    for (const challenge of props.challenges) {
        const key =
            challenge.month === null
                ? 'Anything else'
                : new Date(2000, challenge.month - 1, 1).toLocaleString('en-GB', {
                      month: 'long',
                  });

        byMonth.set(key, [...(byMonth.get(key) ?? []), challenge]);
    }

    return [...byMonth.entries()];
});

const submit = () => form.post(`/spottings/edit/${props.spotting.id}`, { forceFormData: true });

const removePicture = () => {
    form.remove_picture = true;
    form.post(`/spottings/edit/${props.spotting.id}`, {
        forceFormData: true,
        onFinish: () => (form.remove_picture = false),
    });
};

const destroy = () => {
    if (confirm('Delete this spotting? The photo goes with it and this cannot be undone.')) {
        router.delete(`/spottings/${props.spotting.id}`);
    }
};
</script>

<template>
    <Head title="Edit spotting" />

    <SiteLayout title="Edit spotting">
        <form class="max-w-2xl space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
            <FormField label="What was it?" for="challenge_id" :error="form.errors.challenge_id">
                <select
                    id="challenge_id"
                    v-model="form.challenge_id"
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                    required
                >
                    <optgroup v-for="[month, list] in grouped" :key="month" :label="month">
                        <option v-for="challenge in list" :key="challenge.id" :value="challenge.id">
                            {{ challenge.title }}
                        </option>
                    </optgroup>
                </select>
            </FormField>

            <div v-if="props.spotting.picture">
                <p class="font-bold">Current photo</p>
                <MediaImage
                    :src="props.spotting.picture"
                    alt="The photo on this spotting"
                    class="border-bark/30 mt-1 max-w-xs border"
                />
                <button
                    type="button"
                    class="text-berry mt-2 underline"
                    @click="removePicture"
                >
                    Remove this photo
                </button>
            </div>

            <FormField
                :label="props.spotting.picture ? 'Replace the photo' : 'Add a photo'"
                for="picture"
                hint="JPEG, PNG, WebP or HEIC, up to 12MB."
                :error="form.errors.picture"
            >
                <input
                    id="picture"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/heic"
                    class="mt-1 w-full"
                    @input="form.picture = ($event.target as HTMLInputElement).files?.[0] ?? null"
                />
            </FormField>

            <FormField label="Notes" for="notes" :error="form.errors.notes">
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="4"
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField label="Where were you?" for="location" :error="form.errors.location">
                <input
                    id="location"
                    v-model="form.location"
                    type="text"
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <fieldset class="space-y-2">
                <legend class="font-bold">Sharing</legend>
                <label class="flex items-start gap-2">
                    <input v-model="form.gallery" type="checkbox" class="mt-1" />
                    <span>Show this in the public gallery</span>
                </label>
                <label class="flex items-start gap-2">
                    <input v-model="form.map" type="checkbox" class="mt-1" />
                    <span>Show this on the map</span>
                </label>
            </fieldset>

            <div class="flex flex-wrap items-center gap-4">
                <button
                    type="submit"
                    class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>

                <button type="button" class="text-berry underline" @click="destroy">
                    Delete this spotting
                </button>
            </div>
        </form>
    </SiteLayout>
</template>
