# Diginano

The Diginano store: selling digital products (scripts, source code, plugins, themes, templates) with one-time and subscription plans, license management, and multi-currency / multi-payment support.

## Language

**Product**:
A digital good for sale — PHP script, source code, plugin, theme, or template.
_Avoid_: Item, good, asset

**Plan**:
A pricing variant of a Product with exactly one pricing mode — one-time or subscription — and optionally `licenses_per_unit` (how many License keys each unit of quantity grants).
_Avoid_: Package, pricing tier

**License**:
An entitlement to use a Product, issued when an Order succeeds. One unit of Plan quantity grants `licenses_per_unit` License keys (default 1). One-time plans grant an indefinite license; subscription plans grant a license that is valid only while the Subscription is active.
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
A purchase of one or more Plan line items, settled via one PaymentMethod. A successful Order issues the matching Licenses for each line item.
_Avoid_: Transaction, purchase, basket

**OrderItem**:
A line of an Order: a Plan with a quantity. Licenses issued for the item = quantity × the Plan's `licenses_per_unit`.
_Avoid_: Line item (wordy), order line

**Cart**:
The customer's pending selection of Plans with quantities, checked out into an Order.
_Avoid_: Basket

**Subscription**:
A recurring billing arrangement for a subscription-plan Order. While active, its Licenses remain valid.
_Avoid_: Recurring plan

**PaymentMethod**:
The channel used to settle an Order — manual bank transfer, Midtrans, or crypto (via Cryptomus).
_Avoid_: Payment gateway, payment provider

**Coupon**:
A discount code — percentage or fixed amount — that reduces the Order total; on a subscription it applies to the first billing cycle only.
_Avoid_: Discount code, promo, voucher

**Sale**:
An automatic discount attached to a Plan price, optionally for a start/end period, applied without a code. Not applied to renewals.
_Avoid_: Deal, promotion, flash sale

**Renewal**:
The act of extending a Subscription for another cycle by paying for it again. MVP renewals are manual: the customer pays via any PaymentMethod and the Subscription extends.
_Avoid_: Auto-renew, recurring charge

**ExchangeRate**:
The manually-maintained rate used to convert USD base prices into a display currency. Admin-owned and updated by hand, not fetched live.
_Avoid_: Live rate, FX API

**OrderStatus**:
The lifecycle of an Order: `pending`, `awaiting_confirmation` (manual payment), `completed` (licenses issued), `expired`, `cancelled`.
_Avoid_: Order state, order stage
