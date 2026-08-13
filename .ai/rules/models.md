---
paths:
  - 'app/Models/**'
  - app/Models/Order.php
  - app/Models/Subscription.php
  - app/Models/Product.php
  - app/Models/Currency.php
---

# Models

## Display pricing via Currency (ADR-0003)
Harga tersimpan dalam USD; konversi tampilan lewat `Currency::convertUsd($usd)` — rate per 1 USD dari tabel `currencies`. Enum `DisplayCurrency` dan tabel `exchange_rates` sudah dihapus; DB adalah satu-satunya sumber truth. `Currency::required($code)` melempar DomainException bila belum dikonfigurasi admin (sengaja, bukan fallback senyap), `Currency::fromCode($code)` nullable untuk tampilan, `Currency::default()` = satu baris is_default. Kolom string (users.display_currency, orders.currency/settlement_currency) TANPA cast enum dan TANPA foreign key — resolve lewat Currency saat dipakai. Default column DB tidak selalu hadir di memori model hasil factory → beri `$attributes` default pada model (lihat User::$attributes display_currency).

## Harga efektif (sale) & diskon kupon dipusatkan di model
Harga yang dibayar selalu lewat Plan::effectivePriceUsd() (sale aktif dikurangi dari price; cek sale_starts_at/sale_ends_at). CartItem::lineTotalUsd memakai effectivePriceUsd, bukan price mentah. Math kupon di Cart: eligibleSubtotalUsd -> couponDiscountUsd -> totalUsd(?Coupon); diskon persen dari eligible subtotal, fixed = min(fixed_value, eligible). Jangan ulangi logika ini di blade/Livewire.

## Settlement snapshot di setiap checkout (ADR-0003)
Order::checkout() selalu snapshot settlement_currency (code) + settlement_exchange_rate (rate) untuk SEMUA metode: settlement selalu di mata uang gateway — Manual = bank_currency config (default IDR), Midtrans = IDR, Cryptomus = USD (PaymentMethod::settlementCurrency() → Currency). Order status: Manual → AwaitingConfirmation, lainnya → Pending. Akibatnya checkout melempar DomainException jika currency settlement/display belum dikonfigurasi — test yang membuat order wajib menyediakan set currency (lihat helper `seedCurrencies()` idempoten di tests/Pest.php). Jumlah yang ditagih gateway = Order::settlementAmount() (total × settlement_exchange_rate). Render receipt/notifikasi memakai Order::displayCurrency()/settlementCurrency() (fallback ke default bila code hilang).

## Midtrans payment (issue #8)
Order Midtrans dibuat Pending saat checkout, lalu CheckoutPage/OrderReceipt membuat Snap token via MidtransGateway dan redirect ke snap_redirect_url (tersimpan di kolom snap_token/snap_redirect_url). Webhook POST /midtrans/notification (tanpa CSRF, lihat routes/web.php) memverifikasi signature SHA512(order_id+status_code+gross_amount+serverKey) dengan raw gross_amount, hanya menerima order milik metode Midtrans, lalu: settlement/capture+accept → OrderFinalizer (idempoten), capture+challenge → tetap pending, deny/cancel/expire/failure → Cancelled, order terminal → no-op. Kredensial server_key/client_key/is_sandbox dari PaymentMethod.config. Jangan tambah middleware auth di webhook — Midtrans tidak mengirim token.

## Satu subscription per user+plan — cancel-at-period-end + reactivate
subscriptions punya unique(user_id, plan_id) → OrderFinalizer TIDAK pernah create subscription baru untuk user+plan yang sudah punya baris. Subscription::cancel() hanya set cancelled_at (status tetap sampai ends_at lewat); completeCancellation() → Cancelled + deactivate licenses. OrderFinalizer::ensureSubscription: kalau baris isCancelled() → reactivate() (periode baru dari now), selain itu extend(). Order::renewal() menolak jika subscription isCancelled() (isRenewable false).

## Rilis terbaru diurutkan berdasarkan id, bukan created_at
`latestRelease()` dan eager load `product.releases` harus memakai `latest('id')`. `created_at` beresolusi detik sehingga dua rilis yang dibuat dalam detik yang sama berurutan acak. Pakai id sebagai urutan stabil.

## Currency is DB-backed; guard clobbers rate when made default
DisplayCurrency enum + exchange_rates table are gone; currencies is the only source of truth. Currency::saving() forces exchange_rate=1 and is_enabled=true whenever is_default is set, and resets other rows' is_default — so toggling default in admin CL OBBERS the previous default's tuned rate. Deleting the default throws DomainException. Display is plain string codes (no enum cast, no FK); resolve via Currency::required() (throws) / fromCode() (nullable) / default(). Tests must seed the currency set — use idempotent seedCurrencies() in tests/Pest.php.

## Money disimpan dalam sen (integer) via MoneyCast
Semua kolom uang (plans.price/sale_price, orders.subtotal/discount/total, order_items.unit_price/line_total, coupons.fixed_value) adalah unsignedBigInteger sen. Model memakai App\Casts\MoneyCast: baca → float dolar, tulis → sen; nilai yang meleset dari grid sen >0.01 melempar InvalidArgumentException. Atribut model selalu float dolar; jangan kalikan ulang. coupons.value tetap decimal untuk kupon persen. Saat assertDatabaseHas terhadap kolom uang, gunakan nilai sen.
