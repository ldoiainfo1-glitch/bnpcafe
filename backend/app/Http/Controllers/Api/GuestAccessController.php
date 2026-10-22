<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartGuestAccessRequest;
use App\Http\Requests\VerifyGuestAccessRequest;
use App\Http\Resources\UserResource;
use App\Services\GuestIdentityClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GuestAccessController extends Controller
{
    public function start(
        StartGuestAccessRequest $request,
        GuestIdentityClaimService $claims,
    ): JsonResponse {
        $claims->begin(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('phone'),
        );

        return response()->json([
            'message' => 'If the details can be verified, a sign-in code will be sent to the email address.',
        ], 202);
    }

    public function verify(
        VerifyGuestAccessRequest $request,
        GuestIdentityClaimService $claims,
    ): JsonResponse {
        $user = $claims->verify(
            $request->validated('email'),
            $request->validated('code'),
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => ['The sign-in code is invalid or expired. Request a new code and try again.'],
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(200);
    }
}
