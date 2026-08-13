---
paths:
  - 'app/Livewire/**'
  - app/Livewire/DownloadCenter.php
---

# Livewire

## Storefront auth & seam keranjang
Storefront belum punya halaman login khusus: guest dialihkan ke route Filament `filament.admin.auth.login`, dan halaman/aksi yang butuh login (mis. CartPage) menangani guest di dalam komponen (prompt login) — jangan pasang middleware `auth` di route storefront karena route bernama `login` tidak ada. Operasi cart dipusatkan di method model `Cart::add()/setQuantity()/remove()`; kepemilikan item diperiksa via `assertOwns()` (abort 403).

## Berkas rilis privat di disk local, hanya lewat aksi berlisensi
Berkas ProductRelease disimpan di disk `local` (private) tanpa symlink publik. Unduh hanya melalui `DownloadCenter::download()` yang mengabort (403/422) bila user tidak memiliki License usabel; Livewire mengubah `abort()` menjadi HTTP status sehingga test memakai `assertStatus(422)`/`assertForbidden()`, bukan toThrow. Jangan pernah menambah `->disk('public')` pada FileUpload release.
