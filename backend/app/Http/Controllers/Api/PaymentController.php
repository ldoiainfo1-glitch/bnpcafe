<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmRazorpayPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Membership;
use App\Models\Payment;
use App\Services\RazorpayGateway;
use App\Services\RazorpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PaymentResource::collection(
            Payment::query()
                ->where('user_id', request()->user()->id)
                ->with('invoice')
                ->latest()
                ->paginate(15),
        );
    }

    public function createRazorpayOrder(
        Membership $membership,
        RazorpayGateway $gateway,
        RazorpayPaymentService $payments,
    ): JsonResponse {
        if (! $gateway->configured()) {
            throw new HttpException(503, 'The payment provider is not configured.');
        }

        if ($membership->user_id !== request()->user()->id) {
            abort(404);
        }

        $payment = $payments->createOrder($membership, request()->user());

        return response()->json([
            'key_id' => config('services.razorpay.key_id'),
            'order_id' => $payment->provider_order_id,
            'amount' => $payment->amount_minor,
            'currency' => $payment->currency,
            'payment_id' => $payment->id,
        ]);
    }

    public function confirmRazorpayPayment(
        ConfirmRazorpayPaymentRequest $request,
        Membership $membership,
        RazorpayPaymentService $payments,
    ): PaymentResource {
        if ($membership->user_id !== $request->user()->id) {
            abort(404);
        }

        $payment = $payments->confirm($membership, $request->user(), $request->validated());

        return new PaymentResource($payment->load('invoice'));
    }
}
