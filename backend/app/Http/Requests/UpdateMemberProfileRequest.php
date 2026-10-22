<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::Guest, UserRole::Member], true);
    }

    protected function prepareForValidation(): void
    {
        foreach (['phone', 'directory_phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([
                    $field => preg_replace('/[\s().-]+/', '', trim($this->input($field))),
                ]);
            }
        }

        if (is_string($this->input('directory_email'))) {
            $this->merge(['directory_email' => strtolower(trim($this->input('directory_email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'nullable', 'string', 'min:7', 'max:32', 'regex:/^\+?[0-9]+$/'],
            'business_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:120'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:120'],
            'business_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'directory_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'directory_phone' => ['sometimes', 'nullable', 'string', 'min:7', 'max:32', 'regex:/^\+?[0-9]+$/'],
            'show_in_directory' => ['sometimes', 'boolean'],
        ];
    }
}
