<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'summary'         => $this->summary,
            'body'            => $this->body,
            'status'          => $this->status,
            'role'            => $this->role,
            'started_at'      => $this->started_at?->toDateString(),
            'completed_at'    => $this->completed_at?->toDateString(),
            'repository_url'  => $this->repository_url,
            'demo_url'        => $this->demo_url,
            'is_featured'     => $this->is_featured,
            'display_order'   => $this->display_order,
            'views_count'     => (int) ($this->views_count ?? 0),
            'seo_title'       => $this->seo_title,
            'seo_description' => $this->seo_description,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
            'cover'           => new MediaResource($this->whenLoaded('cover')),
            'technologies'    => $this->whenLoaded('technologies', fn () =>
                $this->technologies->map(fn ($t) => [
                    'id'   => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ])
            ),
        ];
    }
}
