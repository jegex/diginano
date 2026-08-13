<?php

namespace App\Enums;

enum PlanPricing: string
{
    case OneTime = 'one-time';
    case Subscription = 'subscription';

    public function isSubscription(): bool
    {
        return $this === self::Subscription;
    }
}
