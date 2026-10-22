<?php

namespace App\Http\Controllers\Api;

use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipApplicationRequest;
use App\Http\Resources\MembershipApplicationResource;
use App\Http\Resources\MembershipResource;
use App\Models\Chapter;
use App\Models\MemberProfile;
use App\Models\MembershipApplication;
use App\Models\MembershipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipApplicationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $applications = request()->user()
            ->membershipApplications()
            ->with(['applicant', 'chapter', 'plan', 'membership'])
            ->latest()
            ->paginate(15);

        return MembershipApplicationResource::collection($applications);
    }

    public function store(StoreMembershipApplicationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $application = DB::transaction(function () use ($request, $data): MembershipApplication {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);
            $chapter = Chapter::query()->lockForUpdate()->findOrFail($data['chapter_id']);
            $plan = MembershipPlan::query()->lockForUpdate()->findOrFail($data['membership_plan_id']);

            if (! $chapter->is_active) {
                throw ValidationException::withMessages([
                    'chapter_id' => ['The selected chapter is no longer accepting applications.'],
                ]);
            }

            if (! $plan->is_active) {
                throw ValidationException::withMessages([
                    'membership_plan_id' => ['The selected membership plan is no longer available.'],
                ]);
            }

            if ($user->membershipApplications()
                ->whereIn('status', ['submitted', 'under_review'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'application' => ['You already have a membership application awaiting review.'],
                ]);
            }

            if ($user->memberships()
                ->whereIn('status', [
                    MembershipStatus::PendingPayment,
                    MembershipStatus::Active,
                ])
                ->exists()) {
                throw ValidationException::withMessages([
                    'membership' => ['You already have a membership awaiting payment or currently active.'],
                ]);
            }

            $application = $user->membershipApplications()->create([
                ...$data,
                'status' => MembershipApplicationStatus::Submitted,
            ]);

            MemberProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'],
                    'business_name' => $data['business_name'],
                    'industry' => $data['industry'],
                    'designation' => $data['designation'] ?? null,
                    'business_address' => $data['business_address'] ?? null,
                ],
            );

            return $application;
        });

        return (new MembershipApplicationResource(
            $application->load(['applicant', 'chapter', 'plan', 'membership']),
        ))->response()->setStatusCode(201);
    }

    public function memberships(): AnonymousResourceCollection
    {
        return MembershipResource::collection(
            request()->user()
                ->memberships()
                ->with(['chapter', 'plan'])
                ->latest()
                ->paginate(15),
        );
    }
}
