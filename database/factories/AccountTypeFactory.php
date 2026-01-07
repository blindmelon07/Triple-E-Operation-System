<?php

namespace Database\Factories;

use App\Enums\AccountCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountType>
 */
class AccountTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('????-???')),
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(AccountCategory::cases()),
            'description' => $this->faker->sentence(),
            'is_system' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the account type is a system account.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    /**
     * Indicate that the account type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
