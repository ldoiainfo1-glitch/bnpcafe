<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreMembershipApplicationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s().-]+/', '', trim($this->input('phone')))]);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('create', MembershipApplication::class)
            && in_array($this->user()?->role, [UserRole::Guest, UserRole::Member], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chapter_id' => ['required', 'integer', Rule::exists('chapters', 'id')->where('is_active', true)],
            'membership_plan_id' => ['required', 'integer', Rule::exists('membership_plans', 'id')->where('is_active', true)],
            'phone' => ['required', 'string', 'min:7', 'max:32', 'regex:/^\+?[0-9]+$/'],
            'business_name' => ['required', 'string', 'max:160'],
            'industry' => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'business_address' => ['nullable', 'string', 'max:2000'],
            'applicant_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
