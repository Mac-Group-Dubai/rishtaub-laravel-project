<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already limited to admins
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['title', 'excerpt', 'category', 'read_time'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim(strip_tags($this->input($field)));
            }
        }
        $this->merge($clean);
    }

    public function rules(): array
    {
        $req = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'slug' => [
                $req, 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('blogs', 'slug')->ignore($this->route('blog')),
            ],
            'title' => [$req, 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:200000'], // markdown, shown escaped by the website
            'cover' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
            'read_time' => ['nullable', 'string', 'max:50'],
            'published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'author_id' => [$req, 'uuid', 'exists:blog_authors,id'],
        ];
    }
}
