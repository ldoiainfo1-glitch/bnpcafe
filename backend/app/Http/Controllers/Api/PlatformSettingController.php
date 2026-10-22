<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class PlatformSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = PlatformSetting::query()
            ->where('is_public', true)
            ->orderBy('key')
            ->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }
}
