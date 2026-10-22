<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PlatformReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from', now()->subDays(30)->toDateString()),
            'to' => $this->input('to', now()->toDateString()),
        ]);
    }

    public function authorize(): bool
    {
        return Gate::allows('view-platform-reports');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $from = $this->input('from');
                    if (! is_string($from) || ! is_string($value)) {
                        return;
                    }

                    $fromDate = \date_create_from_format('!Y-m-d', $from);
                    $toDate = \date_create_from_format('!Y-m-d', $value);

                    if ($fromDate === false
                        || $toDate === false
                        || $fromDate->format('Y-m-d') !== $from
                        || $toDate->format('Y-m-d') !== $value) {
                        return;
                    }

                    if ($fromDate->diff($toDate)->days > 366) {
                        $fail('The report date range cannot exceed 366 days.');
                    }
                },
            ],
        ];
    }
}
