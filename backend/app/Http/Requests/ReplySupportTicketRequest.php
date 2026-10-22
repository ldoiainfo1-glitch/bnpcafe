<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReplySupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('reply', $this->route('supportTicket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $canManage = $user !== null
            && Gate::allows('manage', $this->route('supportTicket'));

        return [
            'message' => ['required', 'string', 'max:5000'],
            'is_internal' => $canManage ? ['sometimes', 'boolean'] : ['prohibited'],
        ];
    }
}
