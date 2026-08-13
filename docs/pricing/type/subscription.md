Subscriptions
--------------
Charge an ongoing, recurring fee for products or services.

## Creating subscription products

Easily set up a subscription-based product to start earning recurring revenue. When adding a new product, be sure to select the “Subscription” option under the pricing section.

From there, you’ll have the option to set the product’s price, billing cycle, and intervals.

Additionally, if you’d like to add a free trial to your subscription product, simply enable the option and set the free trial length.

You can create subscriptions with yearly, monthly, weekly and daily billing intervals. Note that the maximum length of a billing period can be one year, 12 months, 52 weeks or 365 days.

## The subscription lifecycle

When purchased, subscriptions will go through a range of statuses:

* **On trial** - The subscription has started on a trial and is awaiting its first scheduled payment.
* **Active** - The subscription is active and valid.
* **Paused** - Payment collection has been paused and the subscription is still active.
* **Past due** - A renewal payment has failed. will attempt a number of payment retries.
* **Unpaid** - All renewal retries have failed. The subscription may be in dunning.
* **Cancelled** - The customer or merchant has cancelled future payment collection. The subscription is valid until the end of the current billing period.
* **Expired** - The subscription has ended.

> Your customers should retain access to your app or product in all statuses apart from `Expired`.

> Implemented MVP statuses (SubscriptionStatus enum): `active`, `past_due` (3-day grace after the period ends, then cancelled), `cancelled` (cancel-at-period-end, reactivatable via a new order). No trials/paused/unpaid states in the MVP.

## Updating the price of subscription products

If change the price of a subscription product or variant, this will not change the price of existing subscriptions. Only future subscriptions will be charged at the new rate.

Existing subscriptions will always be charged the price that they were created with.
