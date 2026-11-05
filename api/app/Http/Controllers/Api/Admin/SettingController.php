<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json([
            'data' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $items = $request->validated('settings');

        foreach ($items as $item) {
            Setting::set($item['key'], $item['value']);
        }

        $all = Setting::all()->pluck('value', 'key');

        return response()->json([
            'message' => 'Settings updated successfully',
            'data'    => $all,
        ]);
    }
}
