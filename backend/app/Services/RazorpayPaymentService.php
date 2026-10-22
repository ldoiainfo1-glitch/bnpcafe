<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnexpectedValueException;

class RazorpayPaymentService
{
    public function __construct(private readonly RazorpayGateway $gateway) {}

    public function createOrder(Membership $requestedMembership, User $user): Payment
    {
        if (! $this->gateway->configured()) {
            throw new HttpException(503, 'The payment provider is not configured.');
        }

        $payment = DB::transaction(function () use ($requestedMembership, $user): Payment {
            $membership = Membership::query()
                ->whereKey($requestedMembership->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($membership->status !== MembershipStatus::PendingPayment) {
                throw ValidationException::withMessages([
                    'membership' => ['This membership is not awaiting payment.'],
                ]);
            }

            $plan = $membership->plan()->lockForUpdate()->firstOrFail();
            if ($plan->currency !== 'INR') {
                throw ValidationException::withMessages([
                    'membership' => ['Razorpay orders are currently supported only for INR plans.'],
                ]);
            }

            $amountMinor = $this->amountInMinorUnits($plan->price);
            if ($amountMinor < 1) {
                throw ValidationException::withMessages([
                    'membership' => ['A payment order cannot be created for a zero-price plan.'],
                ]);
            }

            $openPayment = $membership->payments()
                ->whereIn('status', [PaymentStatus::Creating, PaymentStatus::Created])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($openPayment?->status === PaymentStatus::Created) {
                return $openPayment;
            }

            if ($openPayment?->status === PaymentStatus::Creating) {
                if ($openPayment->created_at->greaterThan(now()->subMinutes(10))) {
                    throw new HttpException(409, 'A payment order is already being created for this membership.');
                }

                $openPayment->update([
                    'status' => PaymentStatus::Failed,
                    'failure_code' => 'order_creation_timeout',
                ]);
            }

            return $membership->payments()->create([
                'user_id' => $user->id,
                'provider' => 'razorpay',
                'amount_minor' => $amountMinor,
                'currency' => $plan->currency,
                'status' => PaymentStatus::Creating,
            ]);
        });

        if ($payment->status === PaymentStatus::Created) {
            return $payment;
        }

        try {
            $order = $this->gateway->createOrder([
                'amount' => $payment->amount_minor,
                'currency' => $payment->currency,
                'receipt' => 'bnp-payment-'.$payment->id,
            ]);
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            $this->failOrderCreation($payment, 'provider_unavailable');
            Log::warning('Razorpay order creation failed.', [
                'payment_id' => $payment->id,
                'exception' => $exception::class,
            ]);

            throw new HttpException(502, 'The payment provider could not create an order. Please try again.');
        }

        if (! is_string($order['id'] ?? null)
            || ! is_int($order['amount'] ?? null)
            || ! is_string($order['currency'] ?? null)
            || $order['amount'] !== $payment->amount_minor
            || $order['currency'] !== $payment->currency) {
            $this->failOrderCreation($payment, 'invalid_provider_response');
            Log::error('Razorpay returned an invalid order response.', ['payment_id' => $payment->id]);

            throw new HttpException(502, 'The payment provider returned an invalid order response.');
        }

        $payment->update([
            'provider_order_id' => $order['id'],
            'status' => PaymentStatus::Created,
        ]);

        return $payment->refresh();
    }

    /**
     * @param  array{razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string}  $data
     */
    public function confirm(
        Membership $membership,
        User $user,
        array $data,
    ): Payment {
        if (! $this->gateway->configured()) {
            throw new HttpException(503, 'The payment provider is not configured.');
        }

        $payment = Payment::query()
            ->where('membership_id', $membership->id)
            ->where('user_id', $user->id)
            ->where('provider', 'razorpay')
            ->where('provider_order_id', $data['razorpay_order_id'])
            ->firstOrFail();

        $secret = config('services.razorpay.key_secret');
        $expectedSignature = hash_hmac(
            'sha256',
            $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'],
            $secret,
        );

        if (! hash_equals($expectedSignature, $data['razorpay_signature'])) {
            throw ValidationException::withMessages([
                'razorpay_signature' => ['The payment signature is invalid.'],
            ]);
        }

        try {
            $providerPayment = $this->gateway->payment($data['razorpay_payment_id']);
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            Log::warning('Razorpay payment verification request failed.', [
                'payment_id' => $payment->id,
                'exception' => $exception::class,
            ]);

            throw new HttpException(502, 'Payment verification is temporarily unavailable. Please retry.');
        }

        $this->assertCapturedPayment($payment, $providerPayment, $data['razorpay_payment_id']);

        return $this->capture($payment, $data['razorpay_payment_id']);
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    public function captureFromWebhook(array $providerPayment): Payment
    {
        if (($providerPayment['status'] ?? null) !== 'captured'
            || ! is_string($providerPayment['id'] ?? null)
            || ! is_string($providerPayment['order_id'] ?? null)
            || ! is_int($providerPayment['amount'] ?? null)
            || ! is_string($providerPayment['currency'] ?? null)) {
            throw ValidationException::withMessages([
                'payment' => ['The captured payment payload is incomplete or invalid.'],
            ]);
        }

        $payment = Payment::query()
            ->where('provider', 'razorpay')
            ->where('provider_order_id', $providerPayment['order_id'])
            ->firstOrFail();

        $this->assertCapturedPayment($payment, $providerPayment, $providerPayment['id']);

        return $this->capture($payment, $providerPayment['id']);
    }

    private function capture(Payment $payment, string $providerPaymentId): Payment
    {
        return DB::transaction(function () use ($payment, $providerPaymentId): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === PaymentStatus::Paid) {
                if ($payment->provider_payment_id !== $providerPaymentId) {
                    throw ValidationException::withMessages([
                        'payment' => ['A different payment has already been recorded for this order.'],
                    ]);
                }

                return $payment->load('invoice');
            }

            if ($payment->status !== PaymentStatus::Created) {
                throw ValidationException::withMessages([
                    'payment' => ['This payment order is no longer payable.'],
                ]);
            }

            $membership = Membership::query()
                ->with(['plan', 'user'])
                ->lockForUpdate()
                ->findOrFail($payment->membership_id);

            if ($membership->status !== MembershipStatus::PendingPayment) {
                throw ValidationException::withMessages([
                    'membership' => ['This membership is no longer awaiting payment.'],
                ]);
            }

            $paidAt = now();
            $payment->update([
                'provider_payment_id' => $providerPaymentId,
                'status' => PaymentStatus::Paid,
                'failure_code' => null,
                'paid_at' => $paidAt,
            ]);

            Invoice::query()->firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'number' => 'BNP-'.$paidAt->format('Y').'-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT),
                    'membership_id' => $membership->id,
                    'user_id' => $payment->user_id,
                    'amount_minor' => $payment->amount_minor,
                    'currency' => $payment->currency,
                    'issued_at' => $paidAt,
                ],
            );

            $membership->update([
                'status' => MembershipStatus::Active,
                'starts_at' => $paidAt,
                'ends_at' => $paidAt->copy()->addMonths($membership->plan->duration_months),
            ]);
            $membership->user->forceFill([
                'role' => UserRole::Member,
                'chapter_id' => $membership->chapter_id,
            ])->save();

            return $payment->refresh()->load('invoice');
        });
    }

    /**
     * @param  array<string, mixed>  $providerPayment
     */
    private function assertCapturedPayment(
        Payment $payment,
        array $providerPayment,
        string $providerPaymentId,
    ): void {
        if (($providerPayment['status'] ?? null) !== 'captured'
            || ($providerPayment['id'] ?? null) !== $providerPaymentId
            || ($providerPayment['order_id'] ?? null) !== $payment->provider_order_id
            || ($providerPayment['amount'] ?? null) !== $payment->amount_minor
            || ($providerPayment['currency'] ?? null) !== $payment->currency) {
            throw ValidationException::withMessages([
                'payment' => ['The payment provider could not verify a matching captured payment.'],
            ]);
        }
    }

    private function failOrderCreation(Payment $payment, string $failureCode): void
    {
        $payment->update([
            'status' => PaymentStatus::Failed,
            'failure_code' => $failureCode,
        ]);
    }

    private function amountInMinorUnits(string $amount): int
    {
        [$major, $minor] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $major * 100) + (int) str_pad(substr($minor, 0, 2), 2, '0');
    }
}
