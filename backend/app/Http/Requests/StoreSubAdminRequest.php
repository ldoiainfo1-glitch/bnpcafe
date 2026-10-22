<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSubAdminRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => Str::lower(trim($email))]);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && User::query()
                        ->whereRaw('LOWER(email) = ?', [Str::lower($value)])
                        ->exists()) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'chapter_ids' => ['required', 'array', 'min:1'],
            'chapter_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('chapters', 'id')->where('is_active', true),
            ],
        ];
    }
}
