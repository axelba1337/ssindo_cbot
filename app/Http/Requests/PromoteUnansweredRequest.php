<?php

namespace App\Http\Requests;

class PromoteUnansweredRequest extends FaqRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['id'], $rules['is_active']);

        return $rules;
    }
}
