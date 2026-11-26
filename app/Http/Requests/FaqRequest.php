<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already limited to admins
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['question', 'answer', 'category'] as $field) {
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
            'question' => [$req, 'string', 'max:1000'],
            'answer' => [$req, 'string', 'max:10000'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
