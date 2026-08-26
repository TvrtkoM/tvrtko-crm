# Tvrtko CRM — Implementation Log

Running log + index for building the CRM. Full specification: @docs/PROJECT.md

Implement the steps **in sequence**. Each step has its own detailed guide (linked below) and is
self-contained enough to be done and verified on its own. After finishing a step, update its
status here and append any notes/deviations under **Log**.

## Steps

| #   | Step                                | Guide                       | Status         |
| --- | ----------------------------------- | --------------------------- | -------------- |
| 1   | Auth trim + seed DemoUser           | @docs/1_auth_trim.md        | ✅ Done        |
| 2   | Data layer                          | @docs/2_data_layer.md       | ✅ Done        |
| 3   | Backend for Kanban boards           | @docs/3_backend_kanban.md   | ⬜ Not started |
| 4   | PDF route                           | @docs/4_pdf_route.md        | ⬜ Not started |
| 5   | Kanban frontend                     | @docs/5_kanban_frontend.md  | ⬜ Not started |
| 6   | Forms (create/edit + offer shortcut)| @docs/6_forms.md            | ⬜ Not started |
| 7   | Overview: tables + show + dashboard | @docs/7_overview.md         | ⬜ Not started |
| 8   | PDF Blade template                  | @docs/8_pdf_template.md     | ⬜ Not started |

Legend: ⬜ Not started · 🟡 In progress · ✅ Done

## New dependencies (install at the step that needs them)

- `barryvdh/laravel-dompdf` (composer) — **step 4**
- `vue-draggable-plus` (npm) — **step 5**

Both were approved during planning. Scaffolded shadcn-vue component **files** (Textarea, Table,
Pagination — steps 6 & 7) are not npm dependencies.

## Standing rules for every step

- Follow @CLAUDE.md and the guidelines it references.
- Every change must be **programmatically tested** (Pest); run the affected tests with
  `php artisan test --compact`.
- Run `vendor/bin/pint --dirty --format agent` after touching PHP.
- If the frontend changed, `npm run build` must succeed cleanly.
- Do **not** delete existing tests without explicit approval.

## Log

_Append entries as steps complete: date · step · what changed · deviations · follow-ups._

- **2026-08-26 · Step 1 (Auth trim + seed DemoUser).** Trimmed Fortify to email+password login
  only (`config/fortify.php` features `[]`); removed registration, password reset, email
  verification, 2FA, and passkeys end-to-end (routes, `FortifyServiceProvider` view/action/limiter
  bindings, `User` model traits, Vue pages/components). Seeded `DemoUser` /
  `demo@example.com` / `DemoUser` in `DatabaseSeeder`.
  - **Deviations from the doc:** two dead-code spots the doc didn't call out, found only because
    `npm run build` failed on missing imports: `resources/js/pages/auth/ConfirmPassword.vue`
    (kept per the doc) rendered a `<PasskeyVerify />` "confirm with passkey" option — removed it,
    keeping just the password-confirm form. `resources/js/pages/settings/Profile.vue` had a
    "resend verification email" block importing `@/routes/verification` — removed it (dead in
    practice already, since `User` doesn't implement `MustVerifyEmail`).
  - Also deleted now-orphaned files not explicitly named in the doc:
    `components/PasskeyItem.vue`, `ManageTwoFactor.vue`, `TwoFactorSetupModal.vue`,
    `TwoFactorRecoveryCodes.vue`, `composables/useTwoFactorAuth.ts`, and the unused `Passkey` /
    `TwoFactorConfigContent` types in `types/auth.ts` — all were only reachable through components
    the doc did ask to delete.
  - Per user approval, deleted (rather than rewrote) `RegistrationTest`, `PasswordResetTest`,
    `EmailVerificationTest`, `VerificationNotificationTest`, `TwoFactorChallengeTest`, and trimmed
    the 2FA/passkey tests out of `SecurityTest` (kept the password-update tests, generalized the
    password-confirmation-guard test since `RequirePassword` on `settings/security` is unrelated
    to 2FA). Added `tests/Feature/Auth/DemoUserLoginTest.php` for the `/register` 404 + DemoUser
    login acceptance criteria.
  - **Left untouched (harmless per doc):** `two_factor_*` DB columns/migration, `passkeys` table,
    `SecurityController`'s `canManageTwoFactor`/`canManagePasskeys`/`passkeys` props (always
    false/empty now but harmless), `ProfileController`'s `mustVerifyEmail` prop, one still-skipped
    2FA test inside `AuthenticationTest` (kept per doc, self-skips via `skipUnlessFortifyHas`),
    and the `@laravel/passkeys` npm package (unused now, but dependency changes need separate
    approval).
  - **Follow-up:** this sandbox has no `php` on `PATH`; used the Nix-provided
    `php-with-extensions-8.4.24` build directly (the bare `php-8.4.24` derivation is missing most
    extensions, e.g. `filter`, and can't even boot `artisan`).

- **2026-08-26 · Step 2 (Data layer).** Added `CompanyStatus`, `ContactStatus`, `DealStage`,
  `OfferStatus` string-backed enums in `app/Enums` (each with `label()`/`color()`/`options()`,
  the latter shared via `App\Enums\Concerns\HasEnumOptions`). Generated the five models
  (`Company`, `Contact`, `Deal`, `Offer`, `OfferItem`) with one portable migration per table,
  factories, and relationships/casts/`#[Fillable]` per spec. `Offer` gets `subtotal`/
  `tax_amount`/`total` computed accessors and a `booted()` `creating` hook that generates
  `offer_number` (`OFF-{year}-{seq:0000}`, per-year sequence via max-existing-number lookup) and
  defaults `issue_date` to today. `OfferItem` gets a `line_total` accessor. Extended
  `DatabaseSeeder` to create one company per `CompanyStatus` (+3 extra), one contact per
  `ContactStatus` and one deal per `DealStage` for every company, and offers cycling through
  every `OfferStatus` across a shuffled sample of deals (line items auto-attached via
  `OfferFactory::configure()`/`afterCreating`) — every Kanban column has cards for all four
  entities.
  - **Deviations from the doc:** removed the `WithoutModelEvents` trait from `DatabaseSeeder`
    (present in the stock seeder) — it suppresses Eloquent's `creating` event during seeding,
    which would have left every seeded `Offer.offer_number` null and broken the seed on the
    `unique` constraint. Renamed `ContactFactory`'s `New`-status state method to `newStatus()`
    instead of `new()` — `Factory::new()` is a static method on the base `Factory` class, so
    overriding it as an instance state method fatals at runtime.
  - Money/decimal accessors (`line_total`, `subtotal`, `tax_amount`, `total`) return floats
    rounded to 2 decimals rather than strings, to keep arithmetic straightforward for the
    Kanban/forms work in later steps; the underlying columns remain `decimal:2`-cast.
  - Tests: `tests/Feature/Models/{Company,Contact,Deal,Offer,OfferItem}ModelTest.php` cover
    relationship resolution, enum casts, Offer totals math, `offer_number` format/sequencing
    (including per-year reset), and FK on-delete behavior (Company delete nulls Contacts,
    cascades Deals; Deal delete cascades Offers + OfferItems). `php artisan test --compact`:
    40 tests, 39 passed, 1 pre-existing skip (unrelated 2FA test from step 1).
  - `php artisan migrate:fresh --seed` verified clean on SQLite; seeded data spans all statuses
    of all four entities (spot-checked via tinker).
