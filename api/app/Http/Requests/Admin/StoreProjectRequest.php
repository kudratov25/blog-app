<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'slug'            => ['nullable', 'string', 'max:255', Rule::unique('projects', 'slug')],
            'summary'         => ['required', 'string'],
            'body'            => ['nullable', 'string'],
            'status'          => ['nullable', 'in:draft,published,archived'],
            'role'            => ['nullable', 'string', 'max:255'],
            'started_at'      => ['nullable', 'date'],
            'completed_at'    => ['nullable', 'date'],
            'repository_url'  => ['nullable', 'url'],
            'demo_url'        => ['nullable', 'url'],
            'is_featured'     => ['nullable', 'boolean'],
            'display_order'   => ['nullable', 'integer', 'min:0'],
            'cover_media_id'  => ['nullable', 'exists:media,id'],
            'technologies'    => ['nullable', 'array'],
            'technologies.*'  => ['exists:technologies,id'],
            'seo_title'       => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
