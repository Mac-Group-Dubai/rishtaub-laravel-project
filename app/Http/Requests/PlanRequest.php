<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is already limited to admins
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['name', 'duration', 'tagline'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim(strip_tags($this->input($field)));
            }
        }
        if (is_array($this->input('features'))) {
            $clean['features'] = array_map(function ($feature) {
                if (is_array($feature) && is_string($feature['label'] ?? null)) {
                    $feature['label'] = trim(strip_tags($feature['label']));
                }
                return $feature;
            }, $this->input('features'));
        }
        $this->merge($clean);
    }

    public function rules(): array
    {
        $create = $this->isMethod('POST');
        $req = $create ? 'required' : 'sometimes';

        return [
            'id' => $create
                ? ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:plans,id']
                : ['sometimes', 'string'],
            'name' => [$req, 'string', 'max:255'],
            'price' => [$req, 'numeric', 'min:0', 'max:9999999999'],
            'duration' => [$req, 'string', 'max:255'],
            'tagline' => [$req, 'string', 'max:500'],
            'highlight' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'features' => ['sometimes', 'array', 'max:50'],
            'features.*.label' => ['required', 'string', 'max:255'],
            'features.*.included' => ['sometimes', 'boolean'],
        ];
    }
}
