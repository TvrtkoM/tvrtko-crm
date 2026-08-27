<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'contact_id' => null,
            'title' => fake()->words(3, true),
            'value' => fake()->randomFloat(2, 1000, 100000),
            'expected_close_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'status' => fake()->randomElement(DealStage::cases()),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function qualification(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => DealStage::Qualification]);
    }

    public function proposal(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => DealStage::Proposal]);
    }

    public function negotiation(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => DealStage::Negotiation]);
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => DealStage::Won]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => DealStage::Lost]);
    }
}
