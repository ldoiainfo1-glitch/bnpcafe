<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateMembershipPlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper(trim($this->input('currency')))]);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('membershipPlan'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'duration_months' => ['sometimes', 'required', 'integer', 'between:1,120'],
            'features' => ['sometimes', 'nullable', 'array'],
            'features.*' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'between:0,65535'],
        ];
    }
}
