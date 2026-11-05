<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectListResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ViewCounterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::published()
            ->with(['cover', 'technologies'])
            ->orderBy('display_order', 'asc')
            ->latest('id');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $projects = $query->paginate($request->input('per_page', 12));

        return ProjectListResource::collection($projects);
    }

    public function show(Request $request, string $slug, ViewCounterService $viewCounter): ProjectResource
    {
        $project = Project::published()
            ->with(['cover', 'technologies'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Buffer the read in cache (deduplicated per visitor, batch-flushed to DB later)
        $viewCounter->record('project', $project->id, $request);

        return new ProjectResource($project);
    }
}
