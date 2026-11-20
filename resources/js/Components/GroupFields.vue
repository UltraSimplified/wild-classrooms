<script setup lang="ts">
import FormField from '@/Components/FormField.vue';

/*
 * The group details, shared by onboarding and editing so the two forms cannot
 * drift apart.
 */
const form = defineModel<Record<string, any>>({ required: true });

defineProps<{ errors: Record<string, string> }>();
</script>

<template>
    <FormField label="What is your group called?" for="title" :error="errors.title">
        <input
            id="title"
            v-model="form.title"
            type="text"
            required
            class="border-bark/40 bg-parchment mt-1 w-full border"
        />
    </FormField>

    <FormField label="What kind of group?" for="type" :error="errors.type">
        <select
            id="type"
            v-model="form.type"
            required
            class="border-bark/40 bg-parchment mt-1 w-full border"
        >
            <option value="Group">Group</option>
            <option value="Family">Family</option>
            <option value="School">School</option>
            <option value="Other">Other</option>
        </select>
    </FormField>

    <FormField
        label="Tell us about your group"
        for="description"
        hint="Optional. This shows on your woodland page."
        :error="errors.description"
    >
        <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="border-bark/40 bg-parchment mt-1 w-full border"
        />
    </FormField>

    <fieldset class="space-y-4">
        <legend class="font-bold">Where are you?</legend>
        <p class="text-step--1 text-ink-soft !max-w-none">
            We use this to put your woodland on the map.
        </p>

        <FormField label="Address" for="address_line_1" :error="errors.address_line_1">
            <input
                id="address_line_1"
                v-model="form.address_line_1"
                type="text"
                required
                class="border-bark/40 bg-parchment mt-1 w-full border"
            />
        </FormField>

        <FormField label="Address line 2" for="address_line_2" :error="errors.address_line_2">
            <input
                id="address_line_2"
                v-model="form.address_line_2"
                type="text"
                class="border-bark/40 bg-parchment mt-1 w-full border"
            />
        </FormField>

        <div class="grid gap-4 sm:grid-cols-3">
            <FormField label="Town" for="city" :error="errors.city">
                <input
                    id="city"
                    v-model="form.city"
                    type="text"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField label="County" for="county" :error="errors.county">
                <input
                    id="county"
                    v-model="form.county"
                    type="text"
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>

            <FormField label="Postcode" for="postal_code" :error="errors.postal_code">
                <input
                    id="postal_code"
                    v-model="form.postal_code"
                    type="text"
                    required
                    class="border-bark/40 bg-parchment mt-1 w-full border"
                />
            </FormField>
        </div>
    </fieldset>

    <FormField
        label="A photo for your group"
        for="picture"
        hint="Optional. JPEG, PNG, WebP or HEIC, up to 12MB."
        :error="errors.picture"
    >
        <input
            id="picture"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/heic"
            class="mt-1 w-full"
            @input="form.picture = ($event.target as HTMLInputElement).files?.[0] ?? null"
        />
    </FormField>

    <label class="flex items-start gap-2">
        <input v-model="form.public" type="checkbox" class="mt-1" />
        <span>Let other people find our woodland</span>
    </label>
</template>
