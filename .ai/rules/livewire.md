---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Storefront auth & seam keranjang
Storefront belum punya halaman login khusus: guest dialihkan ke route Filament `filament.admin.auth.login`, dan halaman/aksi yang butuh login (mis. CartPage) menangani guest di dalam komponen (prompt login) — jangan pasang middleware `auth` di route storefront karena route bernama `login` tidak ada. Operasi cart dipusatkan di method model `Cart::add()/setQuantity()/remove()`; kepemilikan item diperiksa via `assertOwns()` (abort 403).
