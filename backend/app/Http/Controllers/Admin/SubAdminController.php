<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubAdminRequest;
use App\Http\Requests\UpdateSubAdminChaptersRequest;
use App\Http\Resources\SubAdminResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SubAdminController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return SubAdminResource::collection(
            User::query()
                ->where('role', UserRole::SubAdmin)
                ->with('administeredChapters')
                ->orderBy('name')
                ->paginate(25),
        );
    }

    public function store(StoreSubAdminRequest $request): SubAdminResource
    {
        Gate::authorize('create', User::class);

        $subAdmin = DB::transaction(function () use ($request): User {
            $subAdmin = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Str::random(64),
            ]);
            $subAdmin->forceFill(['role' => UserRole::SubAdmin])->save();
            $subAdmin->administeredChapters()->sync($request->validated('chapter_ids'));

            return $subAdmin;
        });

        return new SubAdminResource($subAdmin->load('administeredChapters'));
    }

    public function update(
        UpdateSubAdminChaptersRequest $request,
        User $subAdmin,
    ): SubAdminResource {
        Gate::authorize('update', $subAdmin);
        $subAdmin->administeredChapters()->sync($request->validated('chapter_ids'));

        return new SubAdminResource($subAdmin->load('administeredChapters'));
    }
}
