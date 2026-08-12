---
paths:
  - 'database/migrations/**'
---

# Migrations

## FK cross-table migrations must be ordered by timestamp
Saat membuat tabel dengan foreign key yang mereferensi tabel lain dalam migrasi terpisah, beri timestamp LEBIH AWAL pada tabel yang direferensikan (contoh: payment_methods dibuat sebelum orders). Jika tidak, MySQL error 1824 "Failed to open the referenced table" dan DDL non-transactional meninggalkan tabel yatim tanpa tercatat di migrations, sehingga run berikutnya gagal "table already exists".
