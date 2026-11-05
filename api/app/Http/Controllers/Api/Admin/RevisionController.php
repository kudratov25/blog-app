<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\RevisionResource;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RevisionController extends Controller
{
    public function index(Post $post): AnonymousResourceCollection
    {
        $revisions = $post->revisions()->with('editor')->latest('id')->get();
        return RevisionResource::collection($revisions);
    }

    public function show(Post $post, PostRevision $revision): RevisionResource
    {
        abort_if($revision->post_id !== $post->id, 404);
        $revision->load('editor');
        return new RevisionResource($revision);
    }

    public function restore(Request $request, Post $post, PostRevision $revision): PostResource
    {
        abort_if($revision->post_id !== $post->id, 404);

        DB::transaction(function () use ($post, $revision, $request) {
            // Snapshot current state before restoring
            PostRevision::create([
                'post_id'       => $post->id,
                'editor_id'     => $request->user()->id,
                'title'         => $post->title,
                'body'          => $post->body,
                'excerpt'       => $post->excerpt,
                'revision_note' => "Pre-restore snapshot before reverting to revision #{$revision->id}",
            ]);

            $post->title = $revision->title;
            $post->body = $revision->body;
            $post->excerpt = $revision->excerpt;
            $post->reading_time = $post->calculateReadingTime();
            $post->save();
        });

        $post->load(['category', 'tags', 'cover', 'author']);

        return new PostResource($post);
    }
}
