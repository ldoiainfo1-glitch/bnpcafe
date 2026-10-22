<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'chapters' => $this->whenLoaded(
                'administeredChapters',
                fn () => $this->administeredChapters->map(fn ($chapter) => [
                    'id' => $chapter->id,
                    'pincode' => $chapter->pincode,
                    'name' => $chapter->name,
                    'is_active' => $chapter->is_active,
                ]),
            ),
        ];
    }
}
