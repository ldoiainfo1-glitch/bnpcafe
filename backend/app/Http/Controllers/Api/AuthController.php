<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): UserResource
    {
        if (! Auth::attempt($request->validated())) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        if ($request->user()->password_set_at === null) {
            $request->user()->forceFill(['password_set_at' => now()])->save();
        }

        return new UserResource($request->user());
    }

    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'password_set_at' => now(),
        ])->save();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->noContent();
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
