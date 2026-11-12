<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectListResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ViewCounterService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::published()
            ->with(['cover', 'technologies'])
            ->newestFirst();

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $projects = $query->paginate($request->input('per_page', 12));

        return ProjectListResource::collection($projects);
    }

    public function show(string $slug): ProjectResource
    {
        $project = Project::published()
            ->with(['cover', 'technologies'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProjectResource($project);
    }

    /**
     * Record a read. Called by the browser a few seconds after the page is
     * shown, so SSR fetches and most crawlers never count as views.
     */
    public function view(Request $request, string $slug, ViewCounterService $viewCounter): Response
    {
        $id = Project::published()->where('slug', $slug)->value('id');

        abort_if($id === null, 404);

        $viewCounter->record('project', $id, $request);

        return response()->noContent();
    }
}
