<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pincode' => $this->pincode,
            'name' => $this->name,
            'state' => $this->state,
            'chapter_head_name' => $this->chapter_head_name,
            'chapter_head_phone' => $this->chapter_head_phone,
            'chapter_head_email' => $this->chapter_head_email,
            'postal_address' => $this->postal_address,
            'about' => $this->about,
            'is_active' => $this->is_active,
        ];
    }
}
