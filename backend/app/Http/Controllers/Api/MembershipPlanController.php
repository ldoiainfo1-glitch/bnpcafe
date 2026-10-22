<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipPlanResource;
use App\Models\MembershipPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MembershipPlanResource::collection(
            MembershipPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(25),
        );
    }
}
