---
paths:
  - 'app/Models/**'
  - app/Models/Order.php
  - app/Models/Subscription.php
  - app/Models/Product.php
---

# Models

## Display pricing via ExchangeRate (ADR-0003)
Harga tersimpan dalam USD; konversi tampilan lewat `ExchangeRate::convert($usd, DisplayCurrency)` — rate per 1 USD, dan `rateFor()` melempar DomainException bila rate non-USD belum dikonfigurasi admin (sengaja, bukan fallback senyap). DisplayCurrency (usd/idr/eur) tinggal di root app seperti enum domain lain. Default column DB tidak selalu hadir di memori model hasil factory → beri `$attributes` default pada model (lihat User::$attributes display_currency).

## Harga efektif (sale) & diskon kupon dipusatkan di model
Harga yang dibayar selalu lewat Plan::effectivePriceUsd() (sale aktif dikurangi dari price; cek sale_starts_at/sale_ends_at). CartItem::lineTotalUsd memakai effectivePriceUsd, bukan price mentah. Math kupon di Cart: eligibleSubtotalUsd -> couponDiscountUsd -> totalUsd(?Coupon); diskon persen dari eligible subtotal, fixed = min(value, eligible). Jangan ulangi logika ini di blade/Livewire.

## Settlement snapshot di setiap checkout (ADR-0003)
Order::checkout() selalu snapshot settlement_currency + settlement_exchange_rate via ExchangeRate::rateFor() untuk SEMUA metode (manual & Midtrans): settlement selalu di mata uang gateway — Manual = bank_currency config (default IDR), Midtrans = IDR. Order status: Manual → AwaitingConfirmation, lainnya → Pending. Akibatnya checkout melempar DomainException jika rate settlement currency (default IDR) belum dikonfigurasi — test yang membuat order manual/midtrans wajib menyediakan ExchangeRate IDR (lihat helper makeManualOrder/makeOrder/makeMidtransOrder). Gunakan ExchangeRate::firstOrCreate agar idempoten. Jumlah yang ditagih gateway = Order::settlementAmount() (total_usd × settlement_exchange_rate).

## Midtrans payment (issue #8)
Order Midtrans dibuat Pending saat checkout, lalu CheckoutPage/OrderReceipt membuat Snap token via MidtransGateway dan redirect ke snap_redirect_url (tersimpan di kolom snap_token/snap_redirect_url). Webhook POST /midtrans/notification (tanpa CSRF, lihat routes/web.php) memverifikasi signature SHA512(order_id+status_code+gross_amount+serverKey) dengan raw gross_amount, hanya menerima order milik metode Midtrans, lalu: settlement/capture+accept → OrderFinalizer (idempoten), capture+challenge → tetap pending, deny/cancel/expire/failure → Cancelled, order terminal → no-op. Kredensial server_key/client_key/is_sandbox dari PaymentMethod.config. Jangan tambah middleware auth di webhook — Midtrans tidak mengirim token.

## Satu subscription per user+plan — cancel-at-period-end + reactivate
subscriptions punya unique(user_id, plan_id) → OrderFinalizer TIDAK pernah create subscription baru untuk user+plan yang sudah punya baris. Subscription::cancel() hanya set cancelled_at (status tetap sampai ends_at lewat); completeCancellation() → Cancelled + deactivate licenses. OrderFinalizer::ensureSubscription: kalau baris isCancelled() → reactivate() (periode baru dari now), selain itu extend(). Order::renewal() menolak jika subscription isCancelled() (isRenewable false).

## Rilis terbaru diurutkan berdasarkan id, bukan created_at
`latestRelease()` dan eager load `product.releases` harus memakai `latest('id')`. `created_at` beresolusi detik sehingga dua rilis yang dibuat dalam detik yang sama berurutan acak. Pakai id sebagai urutan stabil.
