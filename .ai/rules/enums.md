---
paths:
  - 'app/Enums/**'
---

# Enums

## All enums live under app/Enums (App\Enums namespace)
Every enum in the project lives in app/Enums with namespace App\Enums (files: CouponType, LicenseLengthUnit, OrderStatus, PaymentMethodType, PlanStatus, PriceCategory, PricingScheme, RenewalIntervalUnit, SubscriptionStatus, TrialIntervalUnit, UsageAggregation). Never declare an enum at the app/ root (App\ namespace) again — put new enums in app/Enums. `PlanPricing` and `BillingPeriod` have been deleted: pricing category lives in `Price.category` (PriceCategory) and billing interval in `Price.renewal_interval_unit` (RenewalIntervalUnit).
