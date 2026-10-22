<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'phone' => $this->phone,
            'business_name' => $this->business_name,
            'industry' => $this->industry,
            'designation' => $this->designation,
            'business_address' => $this->business_address,
            'bio' => $this->bio,
            'website' => $this->website,
            'directory_email' => $this->directory_email,
            'directory_phone' => $this->directory_phone,
            'show_in_directory' => $this->show_in_directory,
        ];
    }
}
