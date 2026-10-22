<?php

namespace App\Http\Requests;

use App\Models\Chapter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Chapter::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pincode' => ['required', 'string', 'max:20', Rule::unique('chapters', 'pincode')],
            'name' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'chapter_head_name' => ['nullable', 'string', 'max:255'],
            'chapter_head_phone' => ['nullable', 'string', 'max:32'],
            'chapter_head_email' => ['nullable', 'email', 'max:255'],
            'postal_address' => ['nullable', 'string'],
            'about' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
