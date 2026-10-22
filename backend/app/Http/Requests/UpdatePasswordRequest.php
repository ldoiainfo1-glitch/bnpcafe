<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];

        if ($this->user()->password_set_at !== null) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        return $rules;
    }
}
