<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Event::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chapter_id' => ['nullable', 'integer', Rule::exists('chapters', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'venue' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'registration_closes_at' => ['nullable', 'date', 'before_or_equal:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'status' => ['sometimes', 'in:draft,published,cancelled'],
        ];
    }
}
