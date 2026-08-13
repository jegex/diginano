---
paths:
  - 'app/Filament/**'
---

# Filament

## Filament enum Select: bandingkan via $get->enum(), error form bertingkat pakai prefix relationship
Di Filament v5, state Select yang `options(Enum::class)` disimpan sebagai STRING (mis. 'subscription'), bukan enum — jadi closure visibility/required yang membandingkan `$get('category') === PriceCategory::Subscription` selalu false. Gunakan `$get->enum('category', PriceCategory::class) === PriceCategory::Subscription`. Saat assertHasFormErrors untuk field di dalam Fieldset::relationship('price'), gunakan key bertingkat `['price.renewal_interval_unit' => ['required']]` (error di `mountedActions.0.data.price.*`).
