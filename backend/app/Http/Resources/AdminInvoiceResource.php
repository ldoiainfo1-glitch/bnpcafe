<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'membership_id' => $this->membership_id,
            'payment_id' => $this->payment_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'issued_at' => $this->issued_at?->toISOString(),
        ];
    }
}
