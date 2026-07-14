<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request for listings with pagination, sorting and search.
 *
 * Concrete listings (BuildingIndexRequest, etc.) extend this class
 * and add their own rules with array_merge(parent::rules(), [...]).
 */
class PaginatedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules common to every paginated listing.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['integer', 'min:1', 'max:100'],
            'page' => ['integer', 'min:1'],
            'sort' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', 'in:asc,desc'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function sort(): ?string
    {
        return $this->validated('sort');
    }

    public function direction(): string
    {
        return $this->validated('direction') ?? 'asc';
    }
}
