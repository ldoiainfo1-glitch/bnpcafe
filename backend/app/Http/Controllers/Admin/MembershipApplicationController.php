<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewMembershipApplicationRequest;
use App\Http\Resources\MembershipApplicationResource;
use App\Models\MembershipApplication;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MembershipApplicationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MembershipApplication::class);

        $query = MembershipApplication::query()
            ->with(['applicant', 'chapter', 'plan', 'membership'])
            ->latest();

        if (request()->user()->isSubAdmin()) {
            $query->whereHas(
                'chapter.adminUsers',
                fn ($adminUsers) => $adminUsers->whereKey(request()->user()->id),
            );
        }

        return MembershipApplicationResource::collection($query->paginate(25));
    }

    public function show(MembershipApplication $membershipApplication): MembershipApplicationResource
    {
        Gate::authorize('view', $membershipApplication);

        return new MembershipApplicationResource(
            $membershipApplication->load(['applicant', 'chapter', 'plan', 'membership']),
        );
    }

    public function review(
        ReviewMembershipApplicationRequest $request,
        MembershipApplication $membershipApplication,
    ): MembershipApplicationResource {
        Gate::authorize('review', $membershipApplication);

        $application = DB::transaction(function () use ($request, $membershipApplication): MembershipApplication {
            $application = MembershipApplication::query()
                ->lockForUpdate()
                ->findOrFail($membershipApplication->id);

            if (! in_array($application->status, [
                MembershipApplicationStatus::Submitted,
                MembershipApplicationStatus::UnderReview,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['This application has already been reviewed.'],
                ]);
            }

            $status = MembershipApplicationStatus::from($request->validated('status'));
            $application->update([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $request->validated('review_note'),
            ]);

            if ($status === MembershipApplicationStatus::Approved) {
                $application->loadMissing('plan');
                $isFreePlan = $application->plan->price === '0.00';
                $startsAt = $isFreePlan ? now() : null;
                $endsAt = $startsAt?->copy()->addMonths($application->plan->duration_months);

                $application->membership()->firstOrCreate([], [
                    'user_id' => $application->user_id,
                    'membership_plan_id' => $application->membership_plan_id,
                    'chapter_id' => $application->chapter_id,
                    'status' => $isFreePlan ? MembershipStatus::Active : MembershipStatus::PendingPayment,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                if ($isFreePlan) {
                    $application->applicant->forceFill([
                        'role' => UserRole::Member,
                        'chapter_id' => $application->chapter_id,
                    ])->save();
                }
            }

            return $application->refresh()->load(['applicant', 'chapter', 'plan', 'membership']);
        });

        return new MembershipApplicationResource($application);
    }
}
