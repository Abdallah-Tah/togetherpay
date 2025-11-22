<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->words(3, true),
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'billing_mode' => 'manual',
            'cadence' => 'MONTHLY',
            'due_day' => 1,
            'grace_days' => 7,
            'total_amount_cents' => 10000, // $100.00
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the group uses auto-square billing mode.
     */
    public function autoSquare(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_mode' => 'auto_square',
        ]);
    }

    /**
     * Indicate that the group is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }
}
