<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Deal;
use App\Models\Offer;
use App\Models\OfferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
            'title' => fake()->optional()->sentence(4),
            'status' => fake()->randomElement(OfferStatus::cases()),
            'issue_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'valid_until' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'tax_rate' => 25.00,
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Configure the factory to attach a few line items after creating.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Offer $offer): void {
            if ($offer->items()->exists()) {
                return;
            }

            OfferItem::factory()
                ->count(fake()->numberBetween(1, 4))
                ->for($offer)
                ->create();
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => OfferStatus::Draft]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => OfferStatus::Sent]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => OfferStatus::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => OfferStatus::Rejected]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => OfferStatus::Expired]);
    }
}
