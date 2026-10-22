<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactInquiryRequest;
use App\Models\ContactInquiry;
use App\Services\VerifiedIdentityMatcher;
use Illuminate\Http\JsonResponse;

class ContactInquiryController extends Controller
{
    public function store(
        StoreContactInquiryRequest $request,
        VerifiedIdentityMatcher $identityMatcher,
    ): JsonResponse {
        $data = $request->validated();
        $data['user_id'] = $identityMatcher->match($request->user(), $data['email'])?->id;
        ContactInquiry::query()->create($data);

        return response()->json([
            'message' => 'Your inquiry has been received.',
        ], 202);
    }
}
