<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRegistrationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }

        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s().-]+/', '', trim($this->input('phone')))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'min:7', 'max:32', 'regex:/^\+?[0-9]+$/'],
            'business_name' => ['nullable', 'string', 'max:160'],
        ];
    }
}
