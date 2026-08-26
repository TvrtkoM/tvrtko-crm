# Step 2 — Data layer

> Spec: @docs/PROJECT.md (§2 Entities). Progress index: @docs/Log.md
> **Prerequisites:** Step 1. **New dependencies:** none.

**Goal.** Create the five models, their migrations, the four status enums, plus factories and
seeders. Backend controllers/routes come in step 3; nothing here touches the frontend.

Keep migrations **portable** (standard `Schema` builder, no vendor-specific raw SQL) so they run
on both SQLite (dev) and Postgres (prod).

## Enums — `app/Enums` (PHP 8.4 string-backed, TitleCase cases)

Create `CompanyStatus`, `ContactStatus`, `DealStage`, `OfferStatus` with the cases from the spec.
Each enum implements two helpers used by the Kanban + badges:

- `label(): string` — human label.
- `color(): string` — a UI token (e.g. Tailwind color name or hex) for the column accent/badge.
  Terminal stages get distinct colors (Deal `Won` → green, `Lost` → red; Offer `Rejected`/
  `Expired` → red/grey).

Add a static `options(): array` returning `[['value','label','color'], …]` so controllers can
hand the column definitions to the boards/forms as props.

| Enum            | Cases                                           |
| --------------- | ----------------------------------------------- |
| `CompanyStatus` | Lead, Prospect, Customer, Inactive              |
| `ContactStatus` | New, Contacted, Qualified, Unresponsive         |
| `DealStage`     | Qualification, Proposal, Negotiation, Won, Lost |
| `OfferStatus`   | Draft, Sent, Accepted, Rejected, Expired        |

## Models + migrations + factories

Generate together: `php artisan make:model Company -mf` (repeat for `Contact`, `Deal`, `Offer`,
`OfferItem`). One migration per table.

**Columns** (see spec §2 for the full tables). Key points:

- `companies`: `name` req; `email/phone/website/industry/address/city/country` nullable; `status`
  string default `Lead`; `notes` text nullable.
- `contacts`: `company_id` FK **nullable `nullOnDelete`**; `first_name` req; `last_name/email/
  phone/job_title` nullable; `status` default `New`; `notes`.
- `deals`: `company_id` FK **req `cascadeOnDelete`**; `contact_id` FK **nullable `nullOnDelete`**;
  `title` req; `value` `decimal(12,2)` nullable; `expected_close_date` date nullable; `status`
  default `Qualification`; `notes`.
- `offers`: `deal_id` FK **req `cascadeOnDelete`**; `offer_number` string **unique**; `title`
  nullable; `status` default `Draft`; `issue_date` date; `valid_until` date nullable; `tax_rate`
  `decimal(5,2)` default `25.00`; `notes` text nullable.
- `offer_items`: `offer_id` FK **req `cascadeOnDelete`**; `description` req; `quantity`
  `decimal(10,2)` default 1; `unit_price` `decimal(12,2)`; `position` integer default 0.

**Models** (`app/Models`):

- Relationships per spec (hasMany/belongsTo).
- Casts: `status` → the enum class; money → `decimal:2`; dates → `date`.
- `$fillable` for each.
- **Offer:** computed accessors `subtotal` (Σ items `line_total`), `taxAmount`
  (`subtotal × tax_rate / 100`), `total` (`subtotal + taxAmount`). Generate `offer_number` in a
  `booted()` `creating` hook (or a dedicated action): format `OFF-{Y}-{seq:0000}` with the
  sequence resetting per calendar year (max existing number for the year + 1); ensure uniqueness.
- **OfferItem:** `lineTotal` accessor = `quantity × unit_price`.

## Factories & seeders

- Realistic factories; wire FKs through relationships; add states for the various statuses.
  `OfferFactory` attaches a few `OfferItem`s (via `has()` / `afterCreating`).
- Extend **`DatabaseSeeder`** (already seeds DemoUser) to create a handful of companies, each with
  contacts + deals, and some deals carrying offers with line items — spread across statuses so
  **every Kanban column has cards** for the demo.

## Tests

- Model relationships resolve; enum casts work.
- Offer totals: `subtotal`/`taxAmount`/`total` correct for known line items + tax rate.
- `offer_number` generation and per-year sequencing (create several, assert format + increment).
- FK on-delete: deleting a Deal cascades its Offers + OfferItems; deleting a Company nulls its
  Contacts' `company_id`.
- `php artisan test --compact`.

## Acceptance criteria

- `php artisan migrate:fresh --seed` runs clean on SQLite.
- Seeded data spans all statuses of all four entities.
- Tests green.

## Finish

- `vendor/bin/pint --dirty --format agent`; mark step 2 ✅ in @docs/Log.md.
