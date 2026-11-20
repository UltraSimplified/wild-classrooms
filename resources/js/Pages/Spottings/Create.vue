<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import FormField from '@/Components/FormField.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import type { PageProps } from '@/types';

const props = defineProps<{
    challenges: { id: number; title: string; month: number | null; category: string | null }[];
    members: { id: number; title: string }[];
    selectedChallengeId: number | null;
    campaign: string | null;
}>();

const page = usePage<PageProps>();

const form = useForm({
    unit_id: props.members[0]?.id ?? null,
    challenge_id: props.selectedChallengeId,
    notes: '',
    location: '',
    what3words: '',
    campaign: props.campaign ?? '',
    gallery: true,
    map: true,
    picture: null as File | null,
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

const submit = () => form.post('/spottings/add', { forceFormData: true });
</script>

<template>
    <Head title="Add a spotting" />

    <SiteLayout
        title="Add a spotting"
        lede="Tell us what you saw. A photo or a drawing is welcome but not required."
    >
        <form class="max-w-2xl space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
            <FormField label="Who spotted it?" for="unit_id" :error="form.errors.unit_id">
                <select
                    id="unit_id"
                    v-model="form.unit_id"
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                    required
                >
                    <option v-for="member in props.members" :key="member.id" :value="member.id">
                        {{ member.title }}
                    </option>
                </select>
            </FormField>

            <FormField
                label="What did you see?"
                for="challenge_id"
                :error="form.errors.challenge_id"
            >
                <select
                    id="challenge_id"
                    v-model="form.challenge_id"
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                    required
                >
                    <option :value="null" disabled>Choose a species</option>
                    <optgroup v-for="[month, list] in grouped" :key="month" :label="month">
                        <option v-for="challenge in list" :key="challenge.id" :value="challenge.id">
                            {{ challenge.title }}
                        </option>
                    </optgroup>
                </select>
            </FormField>

            <FormField
                label="A photo or drawing"
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

            <FormField
                label="Where were you?"
                for="location"
                hint="A place name is plenty."
                :error="form.errors.location"
            >
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

            <button
                type="submit"
                class="bg-berry hover:bg-berry-bright text-parchment px-5 py-3 font-bold disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Adding…' : 'Add spotting' }}
            </button>

            <p v-if="!page.props.auth.group" class="text-berry font-bold">
                You need a group before you can add a spotting.
            </p>
        </form>
    </SiteLayout>
</template>
