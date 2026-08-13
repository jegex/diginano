<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Notifications\RenewalReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('subscriptions:advance')]
#[Description('Move subscriptions into grace at period end and cancel those past grace')]
class AdvanceSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $enteredGrace = 0;
        $cancelled = 0;

        $dueForGrace = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '<', now())
            ->whereNull('cancelled_at')
            ->with('user')
            ->get();

        foreach ($dueForGrace as $subscription) {
            $subscription->enterGrace();
            $subscription->user->notify(new RenewalReminderNotification($subscription));
            $enteredGrace++;
        }

        $dueForCancellation = Subscription::query()
            ->where(function ($query): void {
                $query->where(fn ($q) => $q
                    ->where('status', SubscriptionStatus::Active)
                    ->where('ends_at', '<', now())
                    ->whereNotNull('cancelled_at'))
                    ->orWhere(fn ($q) => $q
                        ->where('status', SubscriptionStatus::PastDue)
                        ->where('grace_ends_at', '<', now()));
            })
            ->get();

        foreach ($dueForCancellation as $subscription) {
            $subscription->completeCancellation();
            $cancelled++;
        }

        $this->info("Moved {$enteredGrace} subscription(s) into grace, cancelled {$cancelled}.");

        return self::SUCCESS;
    }
}
