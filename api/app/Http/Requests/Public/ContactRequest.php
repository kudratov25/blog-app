<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'subject'       => ['nullable', 'string', 'max:255'],
            'message'       => ['required', 'string', 'min:10', 'max:5000'],
            'website'       => ['nullable', 'string', 'max:0'], // Honeypot field must be empty
            '_rendered_at'  => ['nullable', 'integer'],
            'human_answer'  => ['required', 'integer'],
            'human_expected' => ['required', 'integer', 'same:human_answer'],
        ];
    }

    public function messages(): array
    {
        return [
            'website.max'         => 'Bot submission detected.',
            'human_expected.same' => 'Incorrect security verification answer.',
            'human_answer.required' => 'Please complete the human verification check.',
        ];
    }
}
