<?php

namespace App\Http\Requests\Parents;

use App\Models\ParentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParentSignUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $existingParent = ParentUser::where('clerk_id', $this->input('clerk_id'))->first();

        return [
            'clerk_id' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('parents', 'email')->ignore($existingParent?->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
