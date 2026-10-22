<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage', $this->route('supportTicket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'in:in_progress,escalated,resolved,closed'],
            'priority' => ['sometimes', 'required', 'in:low,normal,high,urgent'],
        ];
    }
}
