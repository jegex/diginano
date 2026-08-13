---
paths:
  - 'database/factories/**'
---

# Factories

## PlanFactory harus chaining-safe: pakai has() + afterCreating, bukan properti custom
Laravel `Factory::state()/for()/has()/count()` return `newInstance()` yang HANYA menyalin states/afterCreating/has/for/count/parent/recycle — properti custom (mis. `$priceAttributes`) TIDAK ikut, jadi state/konfigurasi tidak boleh disimpan di properti instance. Di PlanFactory: harga disusun lewat `has(PriceFactory, 'price')` (aman chaining), dan afterCreating membuat default price hanya bila `price()->exists()` false (karena has() dibuat SEBELUM afterCreating). Jangan kembalikan pola properti custom — sudah terbukti rusak. PlanFactory memakai `withPrice(PriceFactory)` untuk komposisi (mis. subscription + setup fee).
