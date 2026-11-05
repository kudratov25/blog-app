<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Full post resource — includes body. */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'excerpt'         => $this->excerpt,
            'body'            => $this->body,
            'status'          => $this->status,
            'is_featured'     => $this->is_featured,
            'reading_time'    => $this->reading_time,
            'views_count'     => (int) ($this->views_count ?? 0),
            'published_at'    => $this->published_at?->toISOString(),
            'scheduled_at'    => $this->scheduled_at?->toISOString(),
            'seo_title'       => $this->seo_title,
            'seo_description' => $this->seo_description,
            'canonical_url'   => $this->canonical_url,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
            'author'          => $this->whenLoaded('author', fn () => [
                'id'   => $this->author?->id,
                'name' => $this->author?->name,
            ]),
            'category'  => new CategoryResource($this->whenLoaded('category')),
            'cover'     => new MediaResource($this->whenLoaded('cover')),
            'tags'      => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
