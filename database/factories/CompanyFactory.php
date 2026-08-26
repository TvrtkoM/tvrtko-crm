<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->domainName(),
            'industry' => fake()->randomElement([
                'Software', 'Manufacturing', 'Retail', 'Healthcare', 'Finance', 'Construction', 'Hospitality',
            ]),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'status' => fake()->randomElement(CompanyStatus::cases()),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function lead(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => CompanyStatus::Lead]);
    }

    public function prospect(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => CompanyStatus::Prospect]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => CompanyStatus::Customer]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => CompanyStatus::Inactive]);
    }
}
