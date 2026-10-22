<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RazorpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RazorpayWebhookController extends Controller
{
    public function __invoke(Request $request, RazorpayPaymentService $payments): Response
    {
        $secret = config('services.razorpay.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            throw new HttpException(503, 'The payment webhook is not configured.');
        }

        $signature = $request->header('X-Razorpay-Signature');
        if (! is_string($signature)
            || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            throw new HttpException(401, 'The payment webhook signature is invalid.');
        }

        if ($request->input('event') !== 'payment.captured') {
            return response()->noContent();
        }

        $providerPayment = $request->input('payload.payment.entity');
        if (! is_array($providerPayment)) {
            throw new HttpException(400, 'The captured payment webhook payload is invalid.');
        }

        $payments->captureFromWebhook($providerPayment);

        return response()->noContent();
    }
}
