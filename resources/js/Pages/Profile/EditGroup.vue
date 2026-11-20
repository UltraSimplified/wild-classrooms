<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import GroupFields from '@/Components/GroupFields.vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';

interface Member {
    id: number;
    title: string;
    spottings: number;
}

const props = defineProps<{
    group: {
        id: number;
        title: string;
        type: string | null;
        description: string | null;
        address_line_1: string | null;
        address_line_2: string | null;
        city: string | null;
        county: string | null;
        postal_code: string | null;
        public: boolean;
        image: string | null;
    };
    members: Member[];
}>();

/*
 * Members are sent as {id, title} pairs, not bare strings. The old form posted
 * a positional array and matched it against the member list by index, so
 * reordering the inputs reassigned people's spottings to each other.
 */
const form = useForm({
    title: props.group.title ?? '',
    type: props.group.type ?? 'Group',
    description: props.group.description ?? '',
    address_line_1: props.group.address_line_1 ?? '',
    address_line_2: props.group.address_line_2 ?? '',
    city: props.group.city ?? '',
    county: props.group.county ?? '',
    postal_code: props.group.postal_code ?? '',
    public: props.group.public,
    picture: null as File | null,
    members: [
        ...props.members.map((m) => ({ id: m.id, title: m.title })),
        ...Array.from({ length: Math.max(0, 10 - props.members.length) }, () => ({
            id: null as number | null,
            title: '',
        })),
    ],
});

const submit = () => form.post('/profile/update', { forceFormData: true });

const spottingsFor = (id: number | null) =>
    props.members.find((m) => m.id === id)?.spottings ?? 0;
</script>

<template>
    <Head title="Edit your group" />

    <SiteLayout title="Edit your group">
        <form class="max-w-2xl space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
            <img
                v-if="props.group.image"
                :src="props.group.image"
                alt="Your group's photo"
                class="border-bark/30 max-w-xs border"
            />

            <GroupFields v-model="form" :errors="form.errors" />

            <fieldset class="space-y-3">
                <legend class="font-bold">Members</legend>
                <p class="text-step--1 text-ink-soft !max-w-none">
                    Each member has their own tree. Clearing a name removes that member and
                    everything they have spotted, so take care with the ones that have spottings.
                </p>

                <div v-for="(member, index) in form.members" :key="index">
                    <label :for="`member-${index}`" class="sr-only">
                        Member {{ index + 1 }}
                    </label>
                    <div class="flex items-baseline gap-3">
                        <input
                            :id="`member-${index}`"
                            v-model="member.title"
                            type="text"
                            placeholder="Add a member"
                            class="border-bark/40 bg-parchment grow border"
                        />
                        <span
                            v-if="member.id && spottingsFor(member.id) > 0"
                            class="text-step--1 text-ink-soft shrink-0"
                        >
                            {{ spottingsFor(member.id) }}
                            {{ spottingsFor(member.id) === 1 ? 'spotting' : 'spottings' }}
                        </span>
                    </div>
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center gap-4">
                <button
                    type="submit"
                    class="bg-berry hover:bg-berry-bright px-5 py-3 font-bold text-parchment disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>

                <Link href="/profile" class="underline hover:text-leaf">
                    Change your email or password
                </Link>
            </div>
        </form>
    </SiteLayout>
</template>
