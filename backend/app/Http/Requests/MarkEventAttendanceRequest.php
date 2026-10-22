<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MarkEventAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view', $this->route('event'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['attended' => ['required', 'boolean']];
    }
}
