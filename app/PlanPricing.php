<?php

namespace App;

enum PlanPricing: string
{
    case OneTime = 'one-time';
    case Subscription = 'subscription';
}
