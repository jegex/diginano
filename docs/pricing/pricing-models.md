Pricing Models
-----------------
For single payment and subscription products we offer a lot of pricing flexibility with various pricing models.

## Standard pricing

> Available for Single payment and Subscription products.

Standard pricing is the default setting. It lets you charge a single fee for a product or subscription.

Changing the quantity value will simply charge customers a multiple of the regular price. For example, setting the quantity to 3 for the example below would charge the customer `3 × $9.99 = $29.97`.

## Package pricing

> Available for Single payment and Subscription products.

Package pricing lets you charge a fixed amount for a fixed amount of units.

For example, you can sell packages of API credits at a certain price. As usage increases past the package’s unit limit, you would charge for an additional package.

Changing the quantity value will charge customers based on how many packages need to be bought for that quantity.

For example, if you sell 100 credits for $10, setting the quantity to 120 will charge the customer for two packages (`2 × $10 = $20`) because their usage has gone past the 100-credit limit for the first package.

## Tiered pricing models

Tiered pricing models charge customers with much more flexibility and at a unit level, and can be used alongside Usage-based billing for easy usage reporting.

If you want to charge a recurring base fee on volume and graduated subscriptions alongside unit usage, you can add a flat fee to each pricing level.

## Volume pricing

> Available for Subscription products only.

Volume pricing lets you charge a fixed per-unit cost based on the tier customer usage falls into.

For example, you sell software by seats and charge $5/month each up to 5 seats and $4/month per seat after that. Setting the quantity to 8 would charge the customer `8 x $4 = $32`.

You can either use quantity-based billing to charge by usage (which will charge customers up-front), or enable usage-based billing for more flexible reporting (which will charge customers retrospectively).

## Graduated pricing

> Available for Subscription products only.

Graduated pricing lets you charge variable unit prices across different usage tiers (instead of a single unit cost like volume pricing).

For example, you sell software by the number of orders placed in a marketplace, charging a flat fee of $5/month plus $3/order for the first 50 orders, $2/order for the next 50 orders and then $1/order for all additional orders. Setting the quantity to 180 would charge the customer `$5 + (50 × $3) + (50 × $2) + (80 * $1)` = $335.

You can either use quantity-based billing to charge by usage (which will charge customers up-front), or enable usage-based billing for more flexible reporting (which will charge customers retrospectively).

## Setup fees

> Available for Subscription products only.

Setup fee is a one-time charge applied at the start of the subscription period. This fee is charged in addition to regular subscription fees and is typically used to cover the initial costs associated with setting up an account or service.

For example, if a subscription is $10 per month and the setup fee is $15, the initial payment will be `$10 + $15 = $25`, followed by the regular $10 monthly fee.

This option is available for all types of subscription products, including those that offer trials or utilize usage-based billing.
