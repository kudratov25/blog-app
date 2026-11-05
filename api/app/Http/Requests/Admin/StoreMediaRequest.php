<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'      => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB in kilobytes
            ],
            'alt_text'  => ['nullable', 'string', 'max:255'],
            'caption'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Only image files (JPEG, PNG, GIF, WebP) are allowed.',
            'file.max'   => 'File size must not exceed 5MB.',
        ];
    }
}
