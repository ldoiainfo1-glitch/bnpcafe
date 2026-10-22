<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
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
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
        ];
    }
}
