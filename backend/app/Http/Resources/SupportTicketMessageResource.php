<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'author' => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'message' => $this->body,
            'created_at' => $this->created_at?->toISOString(),
        ];

        if ($request->user()?->isSuperAdmin() || $request->user()?->isSubAdmin()) {
            $data['is_internal'] = $this->is_internal;
        }

        return $data;
    }
}
