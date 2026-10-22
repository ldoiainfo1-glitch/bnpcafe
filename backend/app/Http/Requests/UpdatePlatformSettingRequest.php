<?php

namespace App\Http\Requests;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePlatformSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('value'))) {
            $value = trim($this->input('value'));
            if ($this->route('key') === 'support_email') {
                $value = strtolower($value);
            }

            $this->merge(['value' => $value]);
        }
    }

    public function authorize(): bool
    {
        return Gate::allows('manage', PlatformSetting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $key = $this->route('key');
        $rules = match ($key) {
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['required', 'string', 'max:32'],
            'home_announcement' => ['required', 'string', 'max:500'],
            'site_name' => ['required', 'string', 'max:100'],
            default => ['prohibited'],
        };

        return [
            'value' => $rules,
        ];
    }
}
