<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpaceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a space type within a building.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ];
    }
}
