<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipPlanRequest;
use App\Http\Requests\UpdateMembershipPlanRequest;
use App\Http\Resources\MembershipPlanResource;
use App\Models\MembershipPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MembershipPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MembershipPlan::class);

        return MembershipPlanResource::collection(
            MembershipPlan::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(25),
        );
    }

    public function store(StoreMembershipPlanRequest $request): MembershipPlanResource
    {
        Gate::authorize('create', MembershipPlan::class);

        $membershipPlan = MembershipPlan::query()->create($request->validated());

        return new MembershipPlanResource($membershipPlan->refresh());
    }

    public function show(MembershipPlan $membershipPlan): MembershipPlanResource
    {
        Gate::authorize('view', $membershipPlan);

        return new MembershipPlanResource($membershipPlan);
    }

    public function update(
        UpdateMembershipPlanRequest $request,
        MembershipPlan $membershipPlan,
    ): MembershipPlanResource {
        Gate::authorize('update', $membershipPlan);
        $membershipPlan->update($request->validated());

        return new MembershipPlanResource($membershipPlan->refresh());
    }
}
