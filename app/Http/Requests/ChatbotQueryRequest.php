<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('message'))) {
            $this->merge(['message' => trim($this->input('message'))]);
        }
    }

    public function rules(): array
    {
        $tokenRule = ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{43}$/'];

        return [
            'message' => ['required', 'string', 'max:2000'],
            'session_token' => [...$tokenRule, 'prohibits:session_id'],
            // Temporary field-name compatibility for clients upgraded from
            // Phase 3. Its value must still be a new opaque token.
            'session_id' => [...$tokenRule, 'prohibits:session_token'],
        ];
    }
}
