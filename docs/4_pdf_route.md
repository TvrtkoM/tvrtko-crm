# Step 4 — PDF route

> Spec: @docs/PROJECT.md (§7 Offer PDF export). Progress index: @docs/Log.md
> **Prerequisites:** Step 3 (Offer model + routes). **New dependency:** `barryvdh/laravel-dompdf`.

**Goal.** A working `GET /offers/{offer}/pdf` that streams a **downloadable** PDF via dompdf. Use
a **minimal** Blade template here so the route is functional and testable — the full styled
document is built in **step 8**.

## Dependency

- `composer require barryvdh/laravel-dompdf` (approved during planning).
- Optional: `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"` to tune
  `config/dompdf.php` (default paper A4, etc.) — not required.

## Route & controller

- `GET /offers/{offer}/pdf`, behind `auth`, name **`offers.pdf`** (reserved in step 3). Handle it
  in `OfferController@pdf` or a dedicated `OfferPdfController`.
- Eager-load `deal.company`, `deal.contact`, `items`.
- Render and stream as an **attachment**:
  ```php
  return Pdf::loadView('pdf.offer', ['offer' => $offer])
      ->download($offer->offer_number.'.pdf');
  ```
  This sets `Content-Type: application/pdf` and `Content-Disposition: attachment`.

## Minimal template (placeholder)

- `resources/views/pdf/offer.blade.php`: just enough to render — the "Tvrtko CRM" title, the
  `offer_number`, and the computed `total`. Step 8 replaces this with the full layout (bill-to,
  line-items table, totals block, terms).

## Wayfinder / hook

- `php artisan wayfinder:generate` so a typed `offers.pdf` action exists. The actual "Download
  PDF" buttons are added on the show page and list row menu in steps 6/7.

## Tests (feature)

- `GET /offers/{offer}/pdf` → `200`, `Content-Type: application/pdf`,
  `Content-Disposition` contains `attachment` and `filename=OFF-….pdf`, and the body begins with
  the `%PDF` signature.
- Guests are redirected.
- `php artisan test --compact`.

## Acceptance criteria

- Hitting the route downloads a valid, openable PDF file (content minimal for now).

## Finish

- `vendor/bin/pint --dirty --format agent`; mark step 4 ✅ in @docs/Log.md.
