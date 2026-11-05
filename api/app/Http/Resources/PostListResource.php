<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Lightweight post resource for list endpoints — omits body. */
class PostListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'excerpt'      => $this->excerpt,
            'status'       => $this->status,
            'is_featured'  => $this->is_featured,
            'reading_time' => $this->reading_time,
            'views_count'  => (int) ($this->views_count ?? 0),
            'published_at' => $this->published_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
            'author'       => $this->whenLoaded('author', fn () => [
                'id'   => $this->author?->id,
                'name' => $this->author?->name,
            ]),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'cover'    => new MediaResource($this->whenLoaded('cover')),
            'tags'     => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
