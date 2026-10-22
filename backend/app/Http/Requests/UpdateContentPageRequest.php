<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateContentPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('contentPage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_pages', 'slug')->ignore($this->route('contentPage'))],
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'body' => ['sometimes', 'required', 'string', 'max:50000'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
        ];
    }
}
