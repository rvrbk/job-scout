<?php

namespace App\Http\Requests;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LedgerFactRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statement' => ['required', 'string', 'max:1000'],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * Validated attributes ready to be stored on a fact. The "confirmed" checkbox
     * is read with boolean() because checkboxes submit "on".
     *
     * @return array<string, mixed>
     */
    public function factAttributes(?CarbonInterface $currentlyConfirmedAt = null): array
    {
        return [
            ...$this->validated(),
            'confirmed_at' => $this->boolean('confirmed') ? ($currentlyConfirmedAt ?? now()) : null,
        ];
    }
}
