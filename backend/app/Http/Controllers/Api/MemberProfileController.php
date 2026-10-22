<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberProfileRequest;
use App\Http\Resources\MemberProfileResource;
use App\Models\MemberProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        return response()->json([
            'data' => $profile === null ? null : (new MemberProfileResource($profile))->resolve($request),
        ]);
    }

    public function update(UpdateMemberProfileRequest $request): JsonResponse
    {
        $profile = MemberProfile::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        return response()->json([
            'data' => (new MemberProfileResource($profile))->resolve($request),
        ]);
    }
}
