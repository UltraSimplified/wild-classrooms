<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpottingRequest extends FormRequest
{
    /**
     * Authorization is handled by the route's can: middleware against the
     * resolved spotting, so there is nothing extra to check here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'integer', 'exists:challenges,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:191'],
            'what3words' => ['nullable', 'string', 'max:191'],
            'gallery' => ['boolean'],
            'map' => ['boolean'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,webp,heic', 'max:12288'],
            'remove_picture' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gallery' => $this->boolean('gallery'),
            'map' => $this->boolean('map'),
            'remove_picture' => $this->boolean('remove_picture'),
        ]);
    }
}
