<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title'           => ['sometimes', 'required', 'string', 'max:255'],
            'slug'            => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($postId)],
            'excerpt'         => ['nullable', 'string'],
            'body'            => ['sometimes', 'required', 'string'],
            'status'          => ['nullable', 'in:draft,scheduled,published,archived'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'cover_media_id'  => ['nullable', 'exists:media,id'],
            'tags'            => ['nullable', 'array'],
            'tags.*'          => ['exists:tags,id'],
            'is_featured'     => ['nullable', 'boolean'],
            'published_at'    => ['nullable', 'date'],
            'scheduled_at'    => ['nullable', 'date'],
            'seo_title'       => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'canonical_url'   => ['nullable', 'url'],
            'revision_note'   => ['nullable', 'string'],
        ];
    }
}
