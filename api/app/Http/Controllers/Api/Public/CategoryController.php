<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PostListResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $category->loadCount(['posts' => fn ($q) => $q->published()]);

        $posts = $category->posts()
            ->published()
            ->with(['tags', 'cover', 'author'])
            ->latest('published_at')
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'data' => [
                'category' => new CategoryResource($category),
                'posts'    => PostListResource::collection($posts)->response()->getData(true),
            ],
        ]);
    }
}
