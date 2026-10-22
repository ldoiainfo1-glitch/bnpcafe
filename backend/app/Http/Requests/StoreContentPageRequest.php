<?php

namespace App\Http\Requests;

use App\Models\ContentPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreContentPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', ContentPage::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_pages', 'slug')],
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['sometimes', 'in:draft,published'],
        ];
    }
}
