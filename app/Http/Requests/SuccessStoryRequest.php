<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuccessStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already limited to admins
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['couple', 'location', 'date', 'story_date', 'quote'] as $field) {
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
            'couple' => [$req, 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'string', 'max:100'],
            'story_date' => ['nullable', 'string', 'max:100'],
            'quote' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'string', 'max:2000'],
            'published' => ['sometimes', 'boolean'],
            'visible' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
