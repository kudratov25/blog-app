<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Support\SlugGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tags = Tag::withCount('posts')
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = SlugGenerator::generate($data['name'], Tag::class);
        }

        $tag = Tag::create($data);

        return (new TagResource($tag))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Tag $tag): TagResource
    {
        $tag->loadCount('posts');
        return new TagResource($tag);
    }

    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $data = $request->validated();
        if (isset($data['name']) && empty($data['slug']) && ! isset($request->slug)) {
            $data['slug'] = SlugGenerator::generate($data['name'], Tag::class, 'slug', $tag->id);
        }

        $tag->update($data);

        return new TagResource($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }
}
