<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('chapter'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $chapterId = $this->route('chapter');

        return [
            'pincode' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('chapters', 'pincode')->ignore($chapterId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'chapter_head_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'chapter_head_phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'chapter_head_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'postal_address' => ['sometimes', 'nullable', 'string'],
            'about' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
