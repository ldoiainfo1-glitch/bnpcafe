<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicChapterResource;
use App\Models\Chapter;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicChapterController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PublicChapterResource::collection(
            Chapter::query()
                ->where('is_active', true)
                ->orderBy('state')
                ->orderBy('name')
                ->paginate(50),
        );
    }
}
