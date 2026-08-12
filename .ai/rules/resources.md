---
paths:
  - 'app/Filament/Resources/**'
---

# Resources

## Filament v5 resource schema pattern
Filament v5: infolist()/form()/table() di resource didefinisikan via class statis di folder Schemas/ dan Tables/ dengan method configure(Schema|Table $x): Schema|Table. Jangan override infolist di Page ViewRecord dengan signature lama (Filament\Infolists\Infolist) — harus Schema. Closure color/formatStateUsing menerima enum cast (mis. OrderStatus), bukan string.
