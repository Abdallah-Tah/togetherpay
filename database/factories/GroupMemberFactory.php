<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupMember>
 */
class GroupMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'preferred_channel' => fake()->randomElement(['CASH_APP', 'VENMO']),
            'cash_tag' => '$'.fake()->userName(),
            'venmo_username' => '@'.fake()->userName(),
            'share_type' => 'PERCENT',
            'share_value' => 25.00, // 25% default
            'status' => 'active',
            'verified_at' => now(),
        ];
    }

    /**
     * Indicate that the member uses Cash App.
     */
    public function cashApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_channel' => 'CASH_APP',
            'cash_tag' => '$'.fake()->userName(),
        ]);
    }

    /**
     * Indicate that the member uses Venmo.
     */
    public function venmo(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_channel' => 'VENMO',
            'venmo_username' => '@'.fake()->userName(),
        ]);
    }

    /**
     * Indicate that the member uses fixed amount sharing.
     */
    public function fixedAmount(float $amount = 25.00): static
    {
        return $this->state(fn (array $attributes) => [
            'share_type' => 'AMOUNT',
            'share_value' => $amount,
        ]);
    }

    /**
     * Indicate that the member is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => null,
        ]);
    }
}
