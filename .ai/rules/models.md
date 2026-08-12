---
paths:
  - 'app/Models/**'
---

# Models

## Display pricing via ExchangeRate (ADR-0003)
Harga tersimpan dalam USD; konversi tampilan lewat `ExchangeRate::convert($usd, DisplayCurrency)` — rate per 1 USD, dan `rateFor()` melempar DomainException bila rate non-USD belum dikonfigurasi admin (sengaja, bukan fallback senyap). DisplayCurrency (usd/idr/eur) tinggal di root app seperti enum domain lain. Default column DB tidak selalu hadir di memori model hasil factory → beri `$attributes` default pada model (lihat User::$attributes display_currency).
