Usage-based Billing
----------------------
Usage-based billing (sometimes known as “metered billing”) is a system that allows you to easily report customer usage back when selling subscriptions in one of four subscription pricing models.

> With usage-based billing, you charge customers based on their past usage, rather than upfront like with quantity-based subscriptions.

* **Sum of usage during period** - Use this if you want to charge using a total of all usage records reported during the current billing period.
* **Most recent usage during a period** - Use this if you want to charge based on the latest reported usage in the current billing period. If there is no reported usage in the current billing period, usage will be 0.
* **Most recent usage** - Use this if you want to charge based on the latest reported usage (this could have been reported in either the current or a previous billing period).
* **Maximum usage during period** - Use this if you want to charge based on the highest reported usage during the current billing period. If there is no reported usage in the current billing period, usage will be 0.

### Usage-based billing at checkout

When a customer purchases a product with usage-based billing enabled, they will not be charged at checkout unless the **setup fee** option is enabled for the product.

If you set a `quantity` value **at checkout** on a product that has usage-based billing enabled, it will be ignored. The initial charge will always be 0.

All charges for usage-based billing are made retrospectively based on reported usage during the previous billing period.

### Setup fees

When a customer purchases a product with **setup fees**, **they will be immediately charged** with that fee.

For example, when you sell a usage-based product for $10 per unit with a setup fee of $5, a customer will be charged $5 at checkout, and then the regular fee at the end of each billing cycle, depending on usage.
