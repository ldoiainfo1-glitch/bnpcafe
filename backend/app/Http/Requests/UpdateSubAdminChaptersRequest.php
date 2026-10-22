<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateSubAdminChaptersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subAdmin = $this->route('subAdmin');

        return $subAdmin instanceof User && Gate::allows('update', $subAdmin);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'chapter_ids' => ['required', 'array'],
            'chapter_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('chapters', 'id')->where('is_active', true),
            ],
        ];
    }
}
