<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicChapterResource extends JsonResource
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
            'about' => $this->about,
        ];
    }
}
