<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'path'          => $this->path,
            'url'           => $this->url,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'size_bytes'    => $this->size_bytes,
            'width'         => $this->width,
            'height'        => $this->height,
            'alt_text'      => $this->alt_text,
            'caption'       => $this->caption,
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
