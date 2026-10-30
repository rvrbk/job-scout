<?php

namespace App\Http\Requests;

use App\Models\JobSearchPreference;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobSearchPreferenceRequest extends FormRequest
{
    /**
     * List fields arrive from the form as comma or line separated text.
     */
    protected function prepareForValidation(): void
    {
        foreach (['locations', 'keywords', 'exclusions', 'feeds'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([
                    $field => collect(preg_split('/[,\n]/', $this->input($field)))
                        ->map(fn (string $item) => trim($item))
                        ->filter()
                        ->unique(fn (string $item) => mb_strtolower($item))
                        ->values()
                        ->all(),
                ]);
            }
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
            'locations' => ['nullable', 'array', 'max:10'],
            'locations.*' => ['string', 'max:100'],
            'remote_preference' => ['required', Rule::in([JobSearchPreference::REMOTE_ANY, JobSearchPreference::REMOTE_HYBRID, JobSearchPreference::REMOTE_ONLY])],
            'keywords' => ['nullable', 'array', 'max:20'],
            'keywords.*' => ['string', 'max:100'],
            'exclusions' => ['nullable', 'array', 'max:20'],
            'exclusions.*' => ['string', 'max:100'],
            'feeds' => ['nullable', 'array', 'max:10'],
            'feeds.*' => ['url:https', 'max:2048'],
            'min_score' => ['required', 'integer', 'between:0,100'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:10000000', 'gte:salary_min'],
        ];
    }
}
