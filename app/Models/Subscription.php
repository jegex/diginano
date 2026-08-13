<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $plan_id
 * @property int $order_id
 * @property SubscriptionStatus $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $grace_ends_at
 * @property Carbon|null $cancelled_at
 *
 * @method static SubscriptionFactory factory()
 */
#[Fillable(['user_id', 'plan_id', 'order_id', 'status', 'starts_at', 'ends_at', 'grace_ends_at', 'cancelled_at'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function renewalOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isInGrace(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled || $this->cancelled_at !== null;
    }

    public function isRenewable(): bool
    {
        return ! $this->isCancelled();
    }

    public function periodEndLabel(): string
    {
        return $this->ends_at->format('d M Y');
    }

    /**
     * Move the subscription into its 3-day past-due grace period.
     */
    public function enterGrace(?Carbon $now = null): void
    {
        $now ??= now();

        $this->update([
            'status' => SubscriptionStatus::PastDue,
            'grace_ends_at' => $now->copy()->addDays(3),
        ]);
    }

    /**
     * Cancel at the end of the current period: renewals are refused from now
     * on, but access stays until the period actually ends.
     */
    public function cancel(?Carbon $now = null): void
    {
        if ($this->isCancelled()) {
            return;
        }

        $this->update(['cancelled_at' => $now ?? now()]);
    }

    /**
     * Cancel the subscription and deactivate all its licenses.
     */
    public function completeCancellation(): void
    {
        $this->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => $this->cancelled_at ?? now(),
        ]);

        $this->deactivateLicenses();
    }

    /**
     * Restart a cancelled subscription from now (used when the customer buys
     * the same plan again through a fresh checkout).
     */
    public function reactivate(Plan $plan, Order $order, ?Carbon $now = null): void
    {
        $now ??= now();

        $this->update([
            'status' => SubscriptionStatus::Active,
            'order_id' => $order->id,
            'starts_at' => $now,
            'ends_at' => $plan->periodEndsAt($now),
            'grace_ends_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function extend(Plan $plan, Order $order): void
    {
        $base = $this->ends_at->isFuture() ? $this->ends_at : now();

        $this->update([
            'status' => SubscriptionStatus::Active,
            'order_id' => $order->id,
            'starts_at' => $base,
            'ends_at' => $plan->periodEndsAt($base),
            'grace_ends_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function deactivateLicenses(): void
    {
        $this->licenses()->update(['is_active' => false]);
    }
}
