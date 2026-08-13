<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Notifications\LicenseKeysNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Facades\DB;

class OrderFinalizer
{
    /**
     * The single entry point through which an Order becomes completed.
     *
     * Idempotent: a second call for the same Order is a no-op. Rejects
     * expired, cancelled, and already-completed Orders.
     */
    public function finalize(Order $order): Order
    {
        if ($order->status === OrderStatus::Completed) {
            return $order;
        }

        if ($order->status !== OrderStatus::Pending && $order->status !== OrderStatus::AwaitingConfirmation) {
            throw new \DomainException('Only pending or awaiting-confirmation orders can be completed.');
        }

        DB::transaction(function () use ($order): void {
            $order->lockForUpdate();

            if ($order->fresh()->status === OrderStatus::Completed) {
                return;
            }

            foreach ($order->items()->with('plan')->get() as $item) {
                $subscription = $this->ensureSubscription($order, $item);
                $this->issueLicenses($order, $item, $subscription);
            }

            $order->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ]);

            $order->user->notify(new PaymentReceivedNotification($order));
            $order->user->notify(new OrderConfirmationNotification($order));
            $order->user->notify(new LicenseKeysNotification($order->fresh(['items.plan.product'])));
        });

        return $order->fresh(['items.plan.product']);
    }

    /**
     * Issue Licenses for one OrderItem: quantity × licenses_per_unit keys.
     */
    private function issueLicenses(Order $order, OrderItem $item, ?Subscription $subscription): void
    {
        $count = $item->quantity * $item->licenses_per_unit;

        $licenses = [];
        for ($i = 0; $i < $count; $i++) {
            $licenses[] = [
                'key' => License::generateKey(),
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'plan_id' => $item->plan_id,
                'product_id' => $item->product_id,
                'subscription_id' => $subscription?->id,
                'activation_limit' => $item->plan->activation_limit,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($licenses !== []) {
            License::query()->insert($licenses);
        }
    }

    /**
     * Create a Subscription for a subscription-plan item, or extend the
     * existing one for a renewal Order. Returns null for one-time plans.
     */
    private function ensureSubscription(Order $order, OrderItem $item): ?Subscription
    {
        if (! $item->plan->pricing_mode->isSubscription()) {
            return null;
        }

        $subscription = Subscription::query()
            ->where('user_id', $order->user_id)
            ->where('plan_id', $item->plan_id)
            ->first();

        if ($subscription === null) {
            return Subscription::create([
                'user_id' => $order->user_id,
                'plan_id' => $item->plan_id,
                'order_id' => $order->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => $item->plan->periodEndsAt(now()),
                'grace_ends_at' => null,
                'cancelled_at' => null,
            ]);
        }

        if ($subscription->isCancelled()) {
            $subscription->reactivate($item->plan, $order);

            return $subscription;
        }

        $subscription->extend($item->plan, $order);

        return $subscription;
    }
}
