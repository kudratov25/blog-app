<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Http\Resources\PostListResource;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Project;
use App\Models\Tag;
use App\Services\ViewCounterService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(ViewCounterService $viewCounter): JsonResponse
    {
        $publishedPosts = Post::where('status', 'published')->count();
        $draftPosts = Post::where('status', 'draft')->count();
        $scheduledPosts = Post::where('status', 'scheduled')->count();
        $totalProjects = Project::count();
        $unreadMessages = ContactMessage::where('status', 'unread')->count();
        $totalCategories = Category::count();
        $totalTags = Tag::count();
        $totalViews = (int) Post::sum('views_count') + (int) Project::sum('views_count');
        $bufferedViews = $viewCounter->totalBuffered();

        $recentPosts = Post::with(['category', 'tags', 'cover', 'author'])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentMessages = ContactMessage::latest('id')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'stats' => [
                    'published_posts'  => $publishedPosts,
                    'draft_posts'      => $draftPosts,
                    'scheduled_posts'  => $scheduledPosts,
                    'total_projects'   => $totalProjects,
                    'unread_messages'  => $unreadMessages,
                    'total_categories' => $totalCategories,
                    'total_tags'       => $totalTags,
                    'total_views'      => $totalViews,
                    'buffered_views'   => $bufferedViews,
                ],
                'recent_posts'    => PostListResource::collection($recentPosts),
                'recent_messages' => ContactMessageResource::collection($recentMessages),
            ],
        ]);
    }

    public function flushViews(ViewCounterService $viewCounter): JsonResponse
    {
        $result = $viewCounter->flush();

        return response()->json([
            'data' => $result,
        ]);
    }
}
