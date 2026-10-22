<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('event'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chapter_id' => ['sometimes', 'nullable', 'integer', Rule::exists('chapters', 'id')->where('is_active', true)],
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'venue' => ['sometimes', 'required', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'required', 'date'],
            'registration_closes_at' => ['sometimes', 'nullable', 'date'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'status' => ['sometimes', 'in:draft,published,cancelled'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $event = $this->route('event');
                $startsAt = $this->input('starts_at', $event->starts_at);
                $endsAt = $this->input('ends_at', $event->ends_at);
                $registrationClosesAt = $this->input(
                    'registration_closes_at',
                    $event->registration_closes_at,
                );

                if (strtotime((string) $endsAt) <= strtotime((string) $startsAt)) {
                    $validator->errors()->add('ends_at', 'The event end must be after its start.');
                }

                if ($registrationClosesAt !== null
                    && strtotime((string) $registrationClosesAt) > strtotime((string) $startsAt)) {
                    $validator->errors()->add(
                        'registration_closes_at',
                        'Registration must close no later than the event start.',
                    );
                }
            },
        ];
    }
}
