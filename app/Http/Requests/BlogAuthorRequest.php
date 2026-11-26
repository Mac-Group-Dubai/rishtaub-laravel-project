<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already limited to admins
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['name', 'title'] as $field) {
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
            'name' => [$req, 'string', 'max:255'],
            'title' => [$req, 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
