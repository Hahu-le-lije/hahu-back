<?php

namespace App\Http\Requests\Parents;

use Illuminate\Foundation\Http\FormRequest;

class ParentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
