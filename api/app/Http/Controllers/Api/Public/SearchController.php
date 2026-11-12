<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostListResource;
use App\Http\Resources\ProjectListResource;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', $request->input('query', '')));

        if (empty($q)) {
            return response()->json([
                'data' => [
                    'query'    => $q,
                    'posts'    => [],
                    'projects' => [],
                ],
            ]);
        }

        $posts = Post::published()
            ->with(['category', 'tags', 'cover', 'author'])
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('excerpt', 'like', "%{$q}%")
                      ->orWhere('body', 'like', "%{$q}%");
            })
            ->latest('published_at')
            ->limit(10)
            ->get();

        $projects = Project::published()
            ->with(['cover', 'technologies'])
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('summary', 'like', "%{$q}%")
                      ->orWhere('body', 'like', "%{$q}%");
            })
            ->newestFirst()
            ->limit(6)
            ->get();

        return response()->json([
            'data' => [
                'query'    => $q,
                'posts'    => PostListResource::collection($posts),
                'projects' => ProjectListResource::collection($projects),
            ],
        ]);
    }
}
