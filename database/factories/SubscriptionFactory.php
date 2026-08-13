<?php

namespace Database\Factories;

use App\BillingPeriod;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->subscription()->create();

        return [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => Order::factory(),
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'grace_ends_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function forPlan(Plan $plan, User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
            'user_id' => $user->id,
        ]);
    }

    public function pastDue(?Carbon $endsAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PastDue,
            'ends_at' => $endsAt ?? now()->subDay(),
            'grace_ends_at' => now()->addDays(2),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function period(BillingPeriod $period): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => Plan::factory()->subscription($period),
        ]);
    }
}
