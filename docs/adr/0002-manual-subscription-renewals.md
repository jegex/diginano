# Manual subscription renewals

Subscriptions renew manually: at the end of each cycle the customer pays again via any PaymentMethod and the Subscription extends by a period. Auto-charge (stored Midtrans card token, crypto recurring) was rejected for MVP — it is significantly more complex and payment failures are messy to reconcile. This decision shapes the billing UX (an explicit renew action, a 3-day grace period, licenses deactivating when a subscription lapses) and is harder to reverse once customers come to expect manual renewals.
