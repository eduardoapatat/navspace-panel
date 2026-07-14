<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a building.
     *
     * The 3D model is an optional .glb file; if provided it is stored and
     * exposed through model_url for the Unity app to download.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'model' => ['nullable', 'file', 'extensions:glb', 'max:51200'],
        ];
    }
}
