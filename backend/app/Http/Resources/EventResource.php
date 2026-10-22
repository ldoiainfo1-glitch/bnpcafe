<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chapter' => $this->whenLoaded('chapter', fn () => $this->chapter === null
                ? null
                : ['id' => $this->chapter->id, 'name' => $this->chapter->name]),
            'title' => $this->title,
            'description' => $this->description,
            'venue' => $this->venue,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'registration_closes_at' => $this->registration_closes_at?->toISOString(),
            'capacity' => $this->capacity,
            'status' => $this->status->value,
        ];
    }
}
