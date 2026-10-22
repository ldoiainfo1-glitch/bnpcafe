<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryMemberResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DirectoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('view-member-directory');
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:160'],
            'industry' => ['sometimes', 'string', 'max:120'],
        ]);

        $query = User::query()
            ->where('role', 'member')
            ->with(['profile', 'chapter'])
            ->whereHas('memberships', function ($memberships): void {
                $memberships->where('status', 'active')
                    ->where('ends_at', '>', now());
            })
            ->whereHas('profile', function ($profiles) use ($filters): void {
                $profiles->where('show_in_directory', true)
                    ->when(
                        $filters['industry'] ?? null,
                        fn ($profiles, $industry) => $profiles->where('industry', $industry),
                    )
                    ->when($filters['search'] ?? null, function ($profiles, $search): void {
                        $profiles->where(function ($matches) use ($search): void {
                            $matches->where('business_name', 'ilike', '%'.$search.'%')
                                ->orWhere('industry', 'ilike', '%'.$search.'%')
                                ->orWhereHas('user', fn ($users) => $users->where('name', 'ilike', '%'.$search.'%'));
                        });
                    });
            });

        if ($request->user()->isSubAdmin()) {
            $query->whereHas(
                'chapter.adminUsers',
                fn ($adminUsers) => $adminUsers->whereKey($request->user()->id),
            );
        }

        return DirectoryMemberResource::collection(
            $query->orderBy('name')->paginate(25),
        );
    }
}
