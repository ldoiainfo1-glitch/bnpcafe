<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateContactInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('contactInquiry'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:in_progress,resolved'],
        ];
    }
}
