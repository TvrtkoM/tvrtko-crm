<?php

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'job_title' => fake()->jobTitle(),
            'status' => fake()->randomElement(ContactStatus::cases()),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function newStatus(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ContactStatus::New]);
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ContactStatus::Contacted]);
    }

    public function qualified(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ContactStatus::Qualified]);
    }

    public function unresponsive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ContactStatus::Unresponsive]);
    }
}
