<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchedulePostRequest;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Http\Resources\PostListResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostRevision;
use App\Support\SlugGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Post::with(['category', 'tags', 'cover', 'author'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $request->tag_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }

        $perPage = (int) $request->input('per_page', 15);
        $posts = $query->paginate($perPage);

        return PostListResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tags = $data['tags'] ?? [];
        unset($data['tags']);
        $revisionNote = $data['revision_note'] ?? 'Initial creation';
        unset($data['revision_note']);

        $data['author_id'] = $request->user()->id;

        if (empty($data['slug'])) {
            $data['slug'] = SlugGenerator::generate($data['title'], Post::class);
        }

        $post = new Post($data);
        $post->reading_time = $post->calculateReadingTime();

        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $post->published_at = now();
        }

        DB::transaction(function () use ($post, $tags, $revisionNote, $request) {
            $post->save();

            if (! empty($tags)) {
                $post->tags()->sync($tags);
            }

            PostRevision::create([
                'post_id'       => $post->id,
                'editor_id'     => $request->user()->id,
                'title'         => $post->title,
                'body'          => $post->body,
                'excerpt'       => $post->excerpt,
                'revision_note' => $revisionNote,
            ]);
        });

        $post->load(['category', 'tags', 'cover', 'author']);

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Post $post): PostResource
    {
        $post->load(['category', 'tags', 'cover', 'author']);
        return new PostResource($post);
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $data = $request->validated();

        $tags = array_key_exists('tags', $data) ? $data['tags'] : null;
        unset($data['tags']);
        $revisionNote = $data['revision_note'] ?? null;
        unset($data['revision_note']);

        if (isset($data['title']) && empty($data['slug']) && ! isset($request->slug)) {
            $data['slug'] = SlugGenerator::generate($data['title'], Post::class, 'slug', $post->id);
        }

        $post->fill($data);

        if (isset($data['body'])) {
            $post->reading_time = $post->calculateReadingTime();
        }

        if (($post->status === 'published') && empty($post->published_at)) {
            $post->published_at = now();
        }

        DB::transaction(function () use ($post, $tags, $revisionNote, $request) {
            $isDirtyContent = $post->isDirty(['title', 'body', 'excerpt']);

            $post->save();

            if ($tags !== null) {
                $post->tags()->sync($tags);
            }

            if ($isDirtyContent) {
                PostRevision::create([
                    'post_id'       => $post->id,
                    'editor_id'     => $request->user()->id,
                    'title'         => $post->title,
                    'body'          => $post->body,
                    'excerpt'       => $post->excerpt,
                    'revision_note' => $revisionNote ?: 'Edited post',
                ]);
            }
        });

        $post->load(['category', 'tags', 'cover', 'author']);

        return new PostResource($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }

    public function publish(Post $post): PostResource
    {
        $post->status = 'published';
        if (! $post->published_at) {
            $post->published_at = now();
        }
        $post->save();

        $post->load(['category', 'tags', 'cover', 'author']);
        return new PostResource($post);
    }

    public function unpublish(Post $post): PostResource
    {
        $post->status = 'draft';
        $post->save();

        $post->load(['category', 'tags', 'cover', 'author']);
        return new PostResource($post);
    }

    public function schedule(SchedulePostRequest $request, Post $post): PostResource
    {
        $post->status = 'scheduled';
        $post->scheduled_at = $request->scheduled_at;
        $post->save();

        $post->load(['category', 'tags', 'cover', 'author']);
        return new PostResource($post);
    }
}
