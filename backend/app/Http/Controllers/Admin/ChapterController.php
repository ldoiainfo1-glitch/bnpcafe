<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ChapterController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Chapter::class);

        $chapters = Chapter::query()
            ->visibleTo($request->user())
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(25);

        return ChapterResource::collection($chapters);
    }

    public function show(Request $request, int $chapter): ChapterResource
    {
        Gate::authorize('viewAny', Chapter::class);

        $chapter = Chapter::query()
            ->visibleTo($request->user())
            ->findOrFail($chapter);

        Gate::authorize('view', $chapter);

        return new ChapterResource($chapter);
    }

    public function store(StoreChapterRequest $request): ChapterResource
    {
        Gate::authorize('create', Chapter::class);

        $chapter = Chapter::query()->create($request->validated());

        return new ChapterResource($chapter->refresh());
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): ChapterResource
    {
        Gate::authorize('update', $chapter);
        $chapter->update($request->validated());

        return new ChapterResource($chapter->refresh());
    }
}
