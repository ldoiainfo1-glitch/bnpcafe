<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'applicant' => [
                'id' => $this->applicant->id,
                'name' => $this->applicant->name,
                'email' => $this->applicant->email,
            ],
            'chapter' => [
                'id' => $this->chapter->id,
                'name' => $this->chapter->name,
            ],
            'plan' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'price' => $this->plan->price,
                'currency' => $this->plan->currency,
                'duration_months' => $this->plan->duration_months,
            ],
            'phone' => $this->phone,
            'business_name' => $this->business_name,
            'industry' => $this->industry,
            'designation' => $this->designation,
            'business_address' => $this->business_address,
            'applicant_note' => $this->applicant_note,
            'status' => $this->status->value,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'review_note' => $this->review_note,
            'membership' => $this->whenLoaded('membership', fn () => $this->membership === null
                ? null
                : [
                    'id' => $this->membership->id,
                    'status' => $this->membership->status->value,
                    'starts_at' => $this->membership->starts_at?->toISOString(),
                    'ends_at' => $this->membership->ends_at?->toISOString(),
                ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
