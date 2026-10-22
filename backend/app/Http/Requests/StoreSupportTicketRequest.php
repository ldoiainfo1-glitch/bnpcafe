<?php

namespace App\Http\Requests;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', SupportTicket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chapter_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('chapters', 'id')->where('is_active', true),
            ],
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:60'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
