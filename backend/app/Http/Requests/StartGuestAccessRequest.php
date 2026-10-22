<?php

namespace App\Http\Requests;

use App\Services\IdentityValueNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StartGuestAccessRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalizer = app(IdentityValueNormalizer::class);
        $email = $this->input('email');
        $phone = $this->input('phone');

        $this->merge([
            'email' => is_string($email) ? $normalizer->email($email) : $email,
            'phone' => is_string($phone) ? $normalizer->phone($phone) : $phone,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
        ];
    }
}
