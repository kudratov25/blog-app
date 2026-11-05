<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostListResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tags = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $tag->loadCount(['posts' => fn ($q) => $q->published()]);

        $posts = $tag->posts()
            ->published()
            ->with(['category', 'tags', 'cover', 'author'])
            ->latest('published_at')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => [
                'tag'   => new TagResource($tag),
                'posts' => PostListResource::collection($posts)->response()->getData(true),
            ],
        ]);
    }
}
