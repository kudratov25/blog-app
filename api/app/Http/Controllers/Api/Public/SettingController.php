<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $allowed = [
            'site_title',
            'site_subtitle',
            'site_description',
            'author_name',
            'author_email',
            'author_bio',
            'author_about_extended',
            'author_connect_text',
            'author_github',
            'author_linkedin',
            'author_telegram',
        ];

        $settings = Setting::whereIn('key', $allowed)->pluck('value', 'key');

        return response()->json([
            'data' => $settings,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
