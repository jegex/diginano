<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageRecord>
 */
class UsageRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->usageBased()->create();

        return [
            'user_id' => $user->id,
            'subscription_id' => Subscription::factory(),
            'license_id' => License::factory(),
            'plan_id' => $plan->id,
            'quantity' => fake()->numberBetween(1, 100),
            'recorded_at' => now(),
        ];
    }
}
