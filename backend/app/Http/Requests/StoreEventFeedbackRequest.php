<?php

namespace App\Http\Requests;

use App\Models\EventFeedback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreEventFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', EventFeedback::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
