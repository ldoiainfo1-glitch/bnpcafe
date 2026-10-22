<?php

namespace App\Http\Controllers\Api;

use App\Enums\ContentPageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentPageResource;
use App\Models\ContentPage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ContentPageResource::collection(
            ContentPage::query()
                ->where('status', ContentPageStatus::Published)
                ->orderBy('title')
                ->paginate(25),
        );
    }

    public function show(string $slug): ContentPageResource
    {
        $page = ContentPage::query()
            ->where('slug', $slug)
            ->where('status', ContentPageStatus::Published)
            ->firstOrFail();

        return new ContentPageResource($page);
    }
}
