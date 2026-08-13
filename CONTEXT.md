# Diginano

The Diginano store: selling digital products (scripts, source code, plugins, themes, templates) with one-time and subscription plans, license management, and multi-currency / multi-payment support.

## Language

**Product**:
A digital good for sale — PHP script, source code, plugin, theme, or template.
_Avoid_: Item, good, asset

**Plan**:
A SKU (pricing variant) of a Product, paired with exactly one Price (`hasOne`). A Plan carries the license rules: `has_license_keys`, `license_activation_limit` (unlimited via `is_license_limit_unlimited`), and license length for one-time plans (`license_length_value/unit`, or `is_license_length_unlimited`).
_Avoid_: Package, pricing tier, pricing mode

**Price**:
The pricing configuration of a Plan (`hasOne`, 1:1): a `category` (`one_time`, `subscription`, `lead_magnet`, `pwyw`), a `scheme` (`standard`, `package`, `volume`, `graduated`), `unit_price` in USD, optional setup fee, tier tables, renewal/trial intervals, and pwyw suggested/min prices. Money is stored as integer cents and read through `MoneyCast` as float dollars.
_Avoid_: Price model, plan price

**License**:
An entitlement to use a Product, issued when an Order succeeds. One unit of Plan quantity grants exactly one License key. One-time plans grant an indefinite (or license-length-bounded) license; subscription plans grant a license valid only while the Subscription is active.
_Avoid_: Key (the key is a property), serial

**Download**:
Access to a Product's release, available while the License is active — indefinitely for one-time plans, while the Subscription is active for subscription plans.
_Avoid_: File access

**ProductRelease**:
A version of a Product with a changelog. Holders of active Licenses can download it.
_Avoid_: Version, update, file

**Activation**:
Registration of a License key on a customer's site, recorded server-side and bounded per key.
_Avoid_: Seat, instance, registration

**Customer**:
A registered account holder who purchases products and manages their licenses, downloads, and subscriptions.
_Avoid_: User, buyer, client

**Order**:
A purchase of one or more Plan line items, settled via one PaymentMethod. A successful Order issues the matching Licenses for each line item. Zero-value orders (lead magnets, free checkout) complete immediately without a payment method.
_Avoid_: Transaction, purchase, basket

**OrderItem**:
A line of an Order: a Plan with a quantity, snapshotting `unit_price`, `line_total`, and `setup_fee`. Licenses issued for the item = quantity.
_Avoid_: Line item (wordy), order line

**Cart**:
The customer's pending selection of Plans with quantities, checked out into an Order.
_Avoid_: Basket

**Subscription**:
A recurring billing arrangement for a subscription-plan Order. While active, its Licenses remain valid. One subscription per user+plan; renewals are manual (ADR-0002).
_Avoid_: Recurring plan

**UsageRecord**:
A metered usage report for a subscription's current billing period, aggregated at renewal by the Price's `usage_aggregation` mode (sum / last-during-period / last-ever / max).
_Avoid_: Meter, usage metric

**PaymentMethod**:
The channel used to settle an Order — manual bank transfer, Midtrans, or crypto (via Cryptomus).
_Avoid_: Payment gateway, payment provider

**Coupon**:
A discount code — percentage or fixed amount — that reduces the Order subtotal (setup fees excluded); on a subscription it applies to the first billing cycle only.
_Avoid_: Discount code, promo, voucher

**Renewal**:
The act of extending a Subscription for another cycle by paying for it again. MVP renewals are manual: the customer pays via any PaymentMethod and the Subscription extends.
_Avoid_: Auto-renew, recurring charge

**ExchangeRate**:
The manually-maintained rate used to convert USD base prices into a display currency. Admin-owned and updated by hand, not fetched live.
_Avoid_: Live rate, FX API

**OrderStatus**:
The lifecycle of an Order: `pending`, `awaiting_confirmation` (manual payment), `completed` (licenses issued), `expired`, `cancelled`.
_Avoid_: Order state, order stage
