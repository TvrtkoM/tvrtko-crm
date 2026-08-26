<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\OfferItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfferItem>
 */
class OfferItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offer_id' => Offer::factory(),
            'description' => fake()->words(3, true),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'unit_price' => fake()->randomFloat(2, 10, 2000),
            'position' => 0,
        ];
    }
}
