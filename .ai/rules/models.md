---
paths:
  - 'app/Models/**'
---

# Models

## Display pricing via ExchangeRate (ADR-0003)
Harga tersimpan dalam USD; konversi tampilan lewat `ExchangeRate::convert($usd, DisplayCurrency)` — rate per 1 USD, dan `rateFor()` melempar DomainException bila rate non-USD belum dikonfigurasi admin (sengaja, bukan fallback senyap). DisplayCurrency (usd/idr/eur) tinggal di root app seperti enum domain lain. Default column DB tidak selalu hadir di memori model hasil factory → beri `$attributes` default pada model (lihat User::$attributes display_currency).

## Harga efektif (sale) & diskon kupon dipusatkan di model
Harga yang dibayar selalu lewat Plan::effectivePriceUsd() (sale aktif dikurangi dari price; cek sale_starts_at/sale_ends_at). CartItem::lineTotalUsd memakai effectivePriceUsd, bukan price mentah. Math kupon di Cart: eligibleSubtotalUsd -> couponDiscountUsd -> totalUsd(?Coupon); diskon persen dari eligible subtotal, fixed = min(value, eligible). Jangan ulangi logika ini di blade/Livewire.
