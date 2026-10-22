<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectoryMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'chapter' => [
                'id' => $this->chapter->id,
                'name' => $this->chapter->name,
            ],
            'business_name' => $this->profile->business_name,
            'industry' => $this->profile->industry,
            'designation' => $this->profile->designation,
            'bio' => $this->profile->bio,
            'website' => $this->profile->website,
            'email' => $this->profile->directory_email,
            'phone' => $this->profile->directory_phone,
        ];
    }
}
