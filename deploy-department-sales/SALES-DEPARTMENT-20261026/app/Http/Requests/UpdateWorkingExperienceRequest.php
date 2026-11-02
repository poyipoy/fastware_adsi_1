<?php

namespace App\Http\Requests;

class UpdateWorkingExperienceRequest extends StoreWorkingExperienceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['user_id']);

        return $rules;
    }
}
