---
paths:
  - 'resources/views/**'
---

# Views

## Jangan pakai @auth/@endauth di dalam atribut HTML
Blade tidak mengompilasi direktif @auth/@endauth (dan @guest) bila diletakkan di dalam nilai atribut HTML — hasilnya literal teks dan syntax error "endif". Untuk kondisional kecil dalam markup, hitung nilai lebih dulu lewat blok `@php($var = ...)` di puncak view, lalu echo variabelnya.
