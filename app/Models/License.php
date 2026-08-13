<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\LicenseFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $key
 * @property int $user_id
 * @property int $order_id
 * @property int $order_item_id
 * @property int $plan_id
 * @property int $product_id
 * @property int|null $subscription_id
 * @property bool $is_active
 * @property int $activation_limit
 *
 * @method static LicenseFactory factory()
 */
#[Fillable(['key', 'user_id', 'order_id', 'order_item_id', 'plan_id', 'product_id', 'subscription_id', 'is_active', 'activation_limit'])]
class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activation_limit' => 'integer',
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
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<Activation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    /**
     * @return HasMany<Download, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public static function generateKey(): string
    {
        return strtoupper(
            implode('-', [
                Str::random(4),
                Str::random(4),
                Str::random(4),
                Str::random(4),
            ]),
        );
    }

    /**
     * Whether the License currently grants download/usage access.
     *
     * One-time Licenses are valid indefinitely while active; subscription
     * Licenses are valid while their Subscription is active or in grace.
     */
    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->plan->pricing_mode->isSubscription()) {
            return $this->subscription !== null
                && $this->subscription->status !== SubscriptionStatus::Cancelled;
        }

        return true;
    }

    /**
     * The moment the License stops granting access, if any.
     */
    public function validUntil(): ?Carbon
    {
        if ($this->plan->pricing_mode->isSubscription()) {
            return $this->subscription?->grace_ends_at ?? $this->subscription?->ends_at;
        }

        return null;
    }

    /**
     * Register the given domain against this License. Idempotent: re-activating
     * the same domain returns the existing Activation without creating a new one.
     */
    public function activateFor(string $domain): Activation
    {
        $domain = mb_strtolower(trim($domain));

        $existing = $this->activations()->where('domain', $domain)->first();

        if ($existing !== null) {
            return $existing;
        }

        if ($this->activations()->count() >= $this->activation_limit) {
            throw new DomainException("Activation limit of {$this->activation_limit} reached for license {$this->key}.");
        }

        return $this->activations()->create([
            'domain' => $domain,
            'activated_at' => now(),
        ]);
    }

    /**
     * Whether the given domain is a valid, active use of this License.
     */
    public function isValidFor(string $domain): bool
    {
        if (! $this->isUsable()) {
            return false;
        }

        $domain = mb_strtolower(trim($domain));

        return $this->activations()->where('domain', $domain)->exists();
    }

    /**
     * Remove the Activation for the given domain. Idempotent no-op when the
     * domain is not activated.
     */
    public function revokeFor(string $domain): bool
    {
        $domain = mb_strtolower(trim($domain));

        return $this->activations()->where('domain', $domain)->delete() > 0;
    }
}
