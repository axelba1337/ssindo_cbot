<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'intent' => is_string($this->input('intent'))
                ? trim($this->input('intent'))
                : $this->input('intent'),
            'question' => is_string($this->input('question'))
                ? trim($this->input('question'))
                : $this->input('question'),
            'answer' => is_string($this->input('answer'))
                ? trim($this->input('answer'))
                : $this->input('answer'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'nullable',
                'integer',
                Rule::exists('faq_qa', 'id'),
            ],
            'intent' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
