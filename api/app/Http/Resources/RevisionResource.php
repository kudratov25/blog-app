<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'post_id'       => $this->post_id,
            'title'         => $this->title,
            'body'          => $this->body,
            'excerpt'       => $this->excerpt,
            'revision_note' => $this->revision_note,
            'created_at'    => $this->created_at?->toISOString(),
            'editor'        => $this->whenLoaded('editor', fn () => [
                'id'   => $this->editor?->id,
                'name' => $this->editor?->name,
            ]),
        ];
    }
}
