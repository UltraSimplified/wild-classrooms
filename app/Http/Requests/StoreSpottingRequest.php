<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Spotting;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpottingRequest extends FormRequest
{
    /**
     * The spotting must be filed against a member of the user's own group.
     */
    public function authorize(): bool
    {
        $unit = Unit::find($this->input('unit_id'));

        return $unit !== null
            && $this->user()?->can('createFor', [Spotting::class, $unit]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'challenge_id' => ['required', 'integer', 'exists:challenges,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:191'],
            'what3words' => ['nullable', 'string', 'max:191'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'gallery' => ['boolean'],
            'map' => ['boolean'],
            // The old upload had no validation of any kind.
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,webp,heic', 'max:12288'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'picture.max' => 'That photo is larger than 12MB. Please choose a smaller one.',
            'picture.mimes' => 'Please upload a JPEG, PNG, WebP or HEIC photo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gallery' => $this->boolean('gallery'),
            'map' => $this->boolean('map'),
        ]);
    }
}
