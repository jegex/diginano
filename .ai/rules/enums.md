---
paths:
  - 'app/Enums/**'
---

# Enums

## All enums live under app/Enums (App\Enums namespace)
Every enum in the project lives in app/Enums with namespace App\Enums (files: BillingPeriod, CouponType, OrderStatus, PaymentMethodType, PlanPricing, SubscriptionStatus). Never declare an enum at the app/ root (App\ namespace) again — put new enums in app/Enums.
