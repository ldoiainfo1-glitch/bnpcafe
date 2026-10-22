<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class RazorpayGateway
{
    public function configured(): bool
    {
        return filled(config('services.razorpay.key_id'))
            && filled(config('services.razorpay.key_secret'));
    }

    /**
     * @param  array<string, int|string>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        $data = Http::withBasicAuth(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret'),
        )
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://api.razorpay.com/v1/orders', $payload)
            ->throw()
            ->json();

        if (! is_array($data)) {
            throw new UnexpectedValueException('Razorpay returned a non-JSON order response.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function payment(string $paymentId): array
    {
        $data = Http::withBasicAuth(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret'),
        )
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->get('https://api.razorpay.com/v1/payments/'.rawurlencode($paymentId))
            ->throw()
            ->json();

        if (! is_array($data)) {
            throw new UnexpectedValueException('Razorpay returned a non-JSON payment response.');
        }

        return $data;
    }
}
