<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformSettingRequest;
use App\Http\Resources\PlatformSettingResource;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PlatformSettingController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', PlatformSetting::class);

        return PlatformSettingResource::collection(
            PlatformSetting::query()->orderBy('key')->paginate(25),
        );
    }

    public function update(
        UpdatePlatformSettingRequest $request,
        string $key,
    ): JsonResponse {
        $setting = PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $request->validated('value'),
                'is_public' => true,
                'updated_by' => $request->user()->id,
            ],
        );

        return (new PlatformSettingResource($setting->refresh()))
            ->response()
            ->setStatusCode(200);
    }
}
