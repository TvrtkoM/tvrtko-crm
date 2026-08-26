# Step 8 — PDF Blade template

> Spec: @docs/PROJECT.md (§7 Offer PDF export). Progress index: @docs/Log.md
> **Prerequisites:** Step 4 (route + dompdf), Step 2 (offer totals). **New dependencies:** none.

**Goal.** Replace the minimal step-4 placeholder with the full, styled offer document. This is the
final step; after it the project is feature-complete and ready to deploy (see @docs/PROJECT.md
§Deployment).

## Template — `resources/views/pdf/offer.blade.php`

Work within dompdf's constraints: **table-based layout, inline CSS, no flex/grid.** Sections:

- **Header:** plain **"Tvrtko CRM"** text (no logo, no seller/company block).
- **Offer meta:** `offer_number`, `issue_date`, `valid_until`, `status`.
- **Bill-to:** the deal's Company (name / address / city / country / email) and its primary
  Contact (name / email / phone).
- **Line-items table:** description, quantity, unit price, line total — one row per `OfferItem`
  in `position` order.
- **Totals block:** `subtotal → tax (rate %) → total`, right-aligned, in **EUR** formatted like
  `1.234,56 €` (Croatian style). Use a shared formatting helper so the UI and PDF agree.
- **Terms / footer:** the offer's `notes`.

## Verify

- Re-run the step-4 PDF feature test — still `200` / `application/pdf` / `%PDF`, correct filename.
- **Manually download and open** a seeded offer's PDF: check layout, correct bill-to, line items,
  and that totals match the on-screen figures (§2 accessors).
- Optionally assert the rendered PDF text contains the offer number (dompdf text extraction is
  awkward; the header/filename assertions from step 4 may suffice).

## Acceptance criteria

- The downloaded PDF reads as a clean, professional quote with the right bill-to, line items, and
  totals — presentable to the client.
- `php artisan test --compact` green.

## Finish

- `vendor/bin/pint --dirty --format agent` (if PHP touched); mark step 8 ✅ in @docs/Log.md and
  note the project is implementation-complete.
