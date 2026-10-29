<?php

namespace Database\Factories;

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedOn = fake()->dateTimeBetween('-10 years', '-2 years');

        return [
            'user_id' => User::factory(),
            'type' => LedgerEntryType::Experience,
            'title' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'location' => fake()->city(),
            'started_on' => $startedOn->format('Y-m-01'),
            'ended_on' => null,
            'skills' => ['PHP', 'Laravel'],
            'evidence_url' => null,
            'confirmed_at' => now(),
        ];
    }

    public function unconfirmed(): static
    {
        return $this->state(fn () => ['confirmed_at' => null]);
    }

    public function ofType(LedgerEntryType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
