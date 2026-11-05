<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostListResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\ViewCounterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Post::published()
            ->with(['category', 'tags', 'cover', 'author'])
            ->latest('published_at');

        if ($request->filled('category')) {
            $cat = $request->category;
            $query->whereHas('category', function ($q) use ($cat) {
                if (is_numeric($cat)) {
                    $q->where('id', $cat);
                } else {
                    $q->where('slug', $cat);
                }
            });
        }

        if ($request->filled('tag')) {
            $tag = $request->tag;
            $query->whereHas('tags', function ($q) use ($tag) {
                if (is_numeric($tag)) {
                    $q->where('tags.id', $tag);
                } else {
                    $q->where('tags.slug', $tag);
                }
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('excerpt', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 10), 50);
        $posts = $query->paginate($perPage);

        return PostListResource::collection($posts);
    }

    public function show(Request $request, string $slug, ViewCounterService $viewCounter): PostResource
    {
        $post = Post::published()
            ->with(['category', 'tags', 'cover', 'author'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Buffer the read in cache (deduplicated per visitor, batch-flushed to DB later)
        $viewCounter->record('post', $post->id, $request);

        return new PostResource($post);
    }
}
