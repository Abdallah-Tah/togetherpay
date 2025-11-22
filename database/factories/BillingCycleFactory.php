<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingCycle>
 */
class BillingCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return [
            'group_id' => Group::factory(),
            'period_start' => $start,
            'period_end' => $end,
            'due_at' => $end,
            'status' => 'collecting',
            'total_amount_cents' => 10000,
            'collected_amount_cents' => 0,
        ];
    }

    /**
     * Indicate that the cycle is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the cycle is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
