<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderProjectsRequest;
use App\Http\Requests\Admin\StoreProjectRequest;
use App\Http\Requests\Admin\UpdateProjectRequest;
use App\Http\Resources\ProjectListResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Support\SlugGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::with(['cover', 'technologies'])
            ->newestFirst();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }

        $perPage = (int) $request->input('per_page', 20);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $technologies = $data['technologies'] ?? [];
        unset($data['technologies']);

        if (empty($data['slug'])) {
            $data['slug'] = SlugGenerator::generate($data['title'], Project::class);
        }

        $project = new Project($data);

        DB::transaction(function () use ($project, $technologies) {
            $project->save();

            if (! empty($technologies)) {
                $project->technologies()->sync($technologies);
            }
        });

        $project->load(['cover', 'technologies']);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project): ProjectResource
    {
        $project->load(['cover', 'technologies']);
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $data = $request->validated();
        $technologies = array_key_exists('technologies', $data) ? $data['technologies'] : null;
        unset($data['technologies']);

        if (isset($data['title']) && empty($data['slug']) && ! isset($request->slug)) {
            $data['slug'] = SlugGenerator::generate($data['title'], Project::class, 'slug', $project->id);
        }

        $project->fill($data);

        DB::transaction(function () use ($project, $technologies) {
            $project->save();

            if ($technologies !== null) {
                $project->technologies()->sync($technologies);
            }
        });

        $project->load(['cover', 'technologies']);

        return new ProjectResource($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    public function reorder(ReorderProjectsRequest $request): JsonResponse
    {
        $order = $request->validated('order');

        DB::transaction(function () use ($order) {
            foreach ($order as $index => $id) {
                Project::where('id', $id)->update(['display_order' => $index]);
            }
        });

        return response()->json([
            'message' => 'Projects reordered successfully',
        ]);
    }
}
