<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a building.
     *
     * The model is optional: when omitted the existing .glb is kept; when a new
     * .glb is provided it replaces the previous model_url.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'model' => ['nullable', 'file', 'extensions:glb', 'max:51200'],
            // Manual yaw override for the model (auto-detected on upload; this lets
            // the admin fix exporters we don't recognize). One of 0/90/180/270.
            'model_yaw' => ['nullable', 'integer', 'in:0,90,180,270'],
        ];
    }
}
