<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentPageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentPageRequest;
use App\Http\Requests\UpdateContentPageRequest;
use App\Http\Resources\ContentPageResource;
use App\Models\ContentPage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ContentPageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ContentPage::class);

        return ContentPageResource::collection(
            ContentPage::query()->orderBy('title')->paginate(25),
        );
    }

    public function show(ContentPage $contentPage): ContentPageResource
    {
        Gate::authorize('view', $contentPage);

        return new ContentPageResource($contentPage);
    }

    public function store(StoreContentPageRequest $request): ContentPageResource
    {
        Gate::authorize('create', ContentPage::class);
        $page = ContentPage::query()->create([
            ...$request->validated(),
            'status' => $request->validated('status', ContentPageStatus::Draft),
            'updated_by' => $request->user()->id,
        ]);

        return new ContentPageResource($page->refresh());
    }

    public function update(
        UpdateContentPageRequest $request,
        ContentPage $contentPage,
    ): ContentPageResource {
        $contentPage->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return new ContentPageResource($contentPage->refresh());
    }
}
