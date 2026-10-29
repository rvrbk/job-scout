<?php

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerFact>
 */
class LedgerFactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ledger_entry_id' => LedgerEntry::factory(),
            'statement' => fake()->sentence(12),
            'evidence_url' => null,
            'confirmed_at' => now(),
        ];
    }

    public function unconfirmed(): static
    {
        return $this->state(fn () => ['confirmed_at' => null]);
    }
}
