---
paths:
  - 'app/Filament/Resources/**/RelationManagers/**'
---

# Relation Managers

## Relation manager di halaman View default read-only
Di Filament v5, relation manager yang dipasang di halaman View (ViewRecord) default `isReadOnly() === true`, sehingga semua aksi mutasi (DeleteAction, dll.) di-deny dan tersembunyi. Jika relation manager perlu aksi mutasi di halaman View (mis. revoke activation), override `isReadOnly(): bool { return false; }` di relation manager tersebut. Catatan: jangan pasang action delete di halaman view tanpa override ini — test `assertActionVisible` akan gagal dengan pesan yang menyesatkan.
