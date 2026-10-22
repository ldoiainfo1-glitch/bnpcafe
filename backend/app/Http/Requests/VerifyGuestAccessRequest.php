<?php

namespace App\Http\Requests;

use App\Services\IdentityValueNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class VerifyGuestAccessRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge([
                'email' => app(IdentityValueNormalizer::class)->email($email),
            ]);
        }
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
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
