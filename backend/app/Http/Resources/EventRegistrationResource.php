<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'business_name' => $this->business_name,
            'status' => $this->status->value,
            'attended_at' => $this->attended_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
