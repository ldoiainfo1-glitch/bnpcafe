<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventFeedbackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_registration_id' => $this->event_registration_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'submitted_at' => $this->submitted_at?->toISOString(),
        ];
    }
}
