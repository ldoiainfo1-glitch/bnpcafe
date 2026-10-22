<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReviewMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('review', $this->route('membershipApplication'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:under_review,approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
