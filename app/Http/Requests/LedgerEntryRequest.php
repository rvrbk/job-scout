<?php

namespace App\Http\Requests;

use App\Enums\LedgerEntryType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LedgerEntryRequest extends FormRequest
{
    /**
     * Skills arrive as a comma-separated string from the form.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('skills'))) {
            $this->merge([
                'skills' => collect(explode(',', $this->input('skills')))
                    ->map(fn (string $skill) => trim($skill))
                    ->filter()
                    ->unique(fn (string $skill) => mb_strtolower($skill))
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LedgerEntryType::class)],
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'started_on' => ['nullable', 'date_format:Y-m'],
            'ended_on' => ['nullable', 'date_format:Y-m', 'after_or_equal:started_on'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * Validated attributes ready to be stored on a ledger entry.
     *
     * @return array<string, mixed>
     */
    public function entryAttributes(?CarbonInterface $currentlyConfirmedAt = null): array
    {
        $validated = $this->validated();

        foreach (['started_on', 'ended_on'] as $field) {
            $validated[$field] = filled($validated[$field] ?? null)
                ? CarbonImmutable::createFromFormat('!Y-m', $validated[$field])->startOfMonth()
                : null;
        }

        $validated['skills'] ??= [];
        $validated['confirmed_at'] = $this->boolean('confirmed') ? ($currentlyConfirmedAt ?? now()) : null;

        return $validated;
    }
}
