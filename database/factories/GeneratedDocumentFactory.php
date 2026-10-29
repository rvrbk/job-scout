<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedDocument>
 */
class GeneratedDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'user_id' => fn (array $attributes) => JobPosting::query()->whereKey($attributes['job_posting_id'])->value('user_id'),
            'type' => DocumentType::Cv,
            'status' => DocumentStatus::Generating,
            'gaps' => [],
        ];
    }

    public function withStatus(DocumentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function coverLetter(): static
    {
        return $this->state(fn () => ['type' => DocumentType::CoverLetter]);
    }
}
