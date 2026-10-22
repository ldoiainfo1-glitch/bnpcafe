<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'membership' => [
                'id' => $this->membership->id,
                'status' => $this->membership->status->value,
                'chapter' => [
                    'id' => $this->membership->chapter->id,
                    'name' => $this->membership->chapter->name,
                ],
                'plan' => [
                    'id' => $this->membership->plan->id,
                    'name' => $this->membership->plan->name,
                ],
            ],
            'provider' => $this->provider,
            'provider_order_id' => $this->provider_order_id,
            'provider_payment_id' => $this->provider_payment_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'failure_code' => $this->failure_code,
            'paid_at' => $this->paid_at?->toISOString(),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice === null
                ? null
                : ['id' => $this->invoice->id, 'number' => $this->invoice->number]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
