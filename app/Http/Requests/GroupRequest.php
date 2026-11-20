<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'type' => ['required', Rule::in(['Group', 'Family', 'School', 'Other'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'address_line_1' => ['required', 'string', 'max:191'],
            'address_line_2' => ['nullable', 'string', 'max:191'],
            'city' => ['required', 'string', 'max:191'],
            'county' => ['nullable', 'string', 'max:191'],
            'postal_code' => ['required', 'string', 'max:191'],
            'public' => ['boolean'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,webp,heic', 'max:12288'],
            // Up to ten members, as the welcome email promises.
            'members' => ['array', 'max:10'],
            'members.*' => ['nullable', 'string', 'max:191'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['public' => $this->boolean('public')]);
    }
}
