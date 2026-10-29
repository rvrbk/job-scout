<?php

namespace Database\Factories;

use App\Enums\ClaimKind;
use App\Enums\ClaimStatus;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentClaim>
 */
class DocumentClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'generated_document_id' => GeneratedDocument::factory(),
            'ledger_entry_id' => null,
            'section' => DocumentClaim::SECTION_SUMMARY,
            'group' => 0,
            'position' => 0,
            'kind' => ClaimKind::Claim,
            'text' => fake()->sentence(),
            'status' => ClaimStatus::Pending,
            'verifier_note' => null,
        ];
    }

    public function withStatus(ClaimStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
