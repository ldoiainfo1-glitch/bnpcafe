<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'membership_id' => $this->membership_id,
            'provider' => $this->provider,
            'provider_order_id' => $this->provider_order_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'paid_at' => $this->paid_at?->toISOString(),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null
                ? null
                : [
                    'id' => $this->invoice->id,
                    'number' => $this->invoice->number,
                ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
