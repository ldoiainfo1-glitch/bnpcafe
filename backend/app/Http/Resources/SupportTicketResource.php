<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
            ],
            'chapter' => $this->whenLoaded('chapter', fn () => $this->chapter === null
                ? null
                : ['id' => $this->chapter->id, 'name' => $this->chapter->name]),
            'subject' => $this->subject,
            'category' => $this->category,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'assigned_to' => $this->assigned_to,
            'escalated_by' => $this->escalated_by,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'messages' => $this->whenLoaded('messages', fn ($messages) => SupportTicketMessageResource::collection(
                $messages->filter(
                    fn ($message) => ! $message->is_internal
                        || $request->user()?->isSuperAdmin()
                        || $request->user()?->isSubAdmin(),
                ),
            )),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
