---
paths:
  - 'tests/**'
---

# Tests

## Test aksi edit modal Filament pakai mountTableAction
callTableAction('edit', $record) di Filament v5 langsung mengeksekusi save (data record lama valid) sehingga action tidak halt. Untuk menguji modal edit relation manager, pakai mountTableAction('edit', $record) lalu fillForm(...) lalu callMountedTableAction(). Jangan pakai assertTableActionHalted('edit') saat ada record (arguments-nya beda karena recordKey).
