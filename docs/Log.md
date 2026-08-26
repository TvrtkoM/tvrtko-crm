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
| 3   | Backend for Kanban boards           | @docs/3_backend_kanban.md   | ✅ Done        |
| 4   | PDF route                           | @docs/4_pdf_route.md        | ✅ Done        |
| 5   | Kanban frontend                     | @docs/5_kanban_frontend.md  | ✅ Done        |
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

- **2026-08-26 · Step 3 (Backend for Kanban boards).** Added resourceful controllers
  (`Company`, `Contact`, `Deal`, `Offer`), each with `index`/`create`/`store`/`show`/`edit`/
  `update`/`destroy` plus the two Kanban-specific actions: `board` (records eager-loaded with
  their card-content relations, grouped server-side by status value, alongside the enum
  `options()` for columns) and `updateStatus` (lightweight inline `Rule::enum(...)` validation,
  `PATCH .../status`, no full-form validation). `Store{Entity}Request`/`Update{Entity}Request`
  Form Requests cover the field rules from §2 (`authorize()` → `true`, single seeded user).
  `OfferController@store`/`@update` write `OfferItem`s from the `items[]` payload (update
  replaces all items — delete + recreate — since there's no other ordering signal); `create`
  honors an optional `?deal=` query param, eager-loading and passing the bound `Deal` so the
  frontend can lock the picker. Routes: `Route::resource()` per entity plus `GET
  {entity}/board` (`{entity}.board`) and `PATCH {entity}/{model}/status` (`{entity}.status`)
  registered *before* the resource so they aren't swallowed by the `show` route. Ran
  `php artisan wayfinder:generate` for typed frontend actions. Controllers flash a success
  toast via `Inertia::flash('toast', [...])` (existing convention from `ProfileController`) and
  redirect to the record's `show` route (create/update) or the entity's `board` (destroy).
  - **Deviation from the doc:** added `$appends = ['subtotal', 'tax_amount', 'total']` to
    `Offer` and `$appends = ['line_total']` to `OfferItem` — these are computed accessors
    (added in step 2) that don't serialize into Inertia props/JSON by default without being
    appended, and the Offers board/show need `total` for card content (§3).
  - **Vue stubs:** created a minimal placeholder page per route (`resources/js/pages/
    {Company,Contact,Deal,Offer}/{Index,Board,Create,Show,Edit}.vue`, 20 files) — the doc
    explicitly allows stubs at this step, and Inertia's v3.3.1 testing helpers now verify the
    referenced page component file exists on disk, so `assertInertia` calls fail without them.
    Real UI lands in steps 5–7.
  - Tests: `tests/Feature/Controllers/{Company,Contact,Deal,Offer}ControllerTest.php` — guest
    redirects on every route, `store`/`update` 422-on-invalid and success+flash+redirect,
    `updateStatus` success/enum-rejection, `board` component+grouped-props via
    `assertInertia`, `destroy` cascade/null-on-delete behavior, and (Offer-specific) `items[]`
    persistence + `offer_number` generation + the `?deal` create-time lock. `php artisan test
    --compact`: 77 tests, 76 passed, 1 pre-existing skip. `vendor/bin/pint --format agent` on
    all touched/new files: clean. `npm run build`: clean.

- **2026-08-26 · Step 4 (PDF route).** Added `barryvdh/laravel-dompdf` (`^3.1`). New
  `GET /offers/{offer}/pdf` route (named `offers.pdf`) and `OfferController@pdf`, eager-loading
  `deal.company`, `deal.contact`, `items` and streaming
  `Pdf::loadView('pdf.offer', [...])->download($offer->offer_number.'.pdf')`. Added the minimal
  placeholder template `resources/views/pdf/offer.blade.php` (title, offer number, total) — step
  8 replaces it with the full layout. Regenerated Wayfinder actions.
  - No deviations from the doc.
  - Tests: extended `tests/Feature/Controllers/OfferControllerTest.php` with the guest-redirect
    case for `offers.pdf` and a new test asserting `200`, `application/pdf`,
    `Content-Disposition: attachment` with the `{offer_number}.pdf` filename, and a `%PDF` body
    signature. `php artisan test --compact`: 78 tests, 77 passed, 1 pre-existing skip.
    `vendor/bin/pint --format agent`: clean. No frontend changes, so no `npm run build` needed.

- **2026-08-26 · Step 5 (Kanban frontend).** Installed `vue-draggable-plus` (approved). Added the
  generic `resources/js/components/KanbanBoard.vue`: props are `columns` (the enum `options()`
  payload), `cards` (records grouped by status), `cardsProp` (the Inertia prop name), the
  Wayfinder `statusAction`, plus `idKey`/`statusKey`/`errorMessage` config. Columns render every
  enum case in order with a colored dot (`color()`), label, and live count badge; empty columns
  show a muted "No cards" placeholder; `cards === undefined` renders `Skeleton` cards. The board
  is horizontally scrollable with per-column vertical scroll, and dark-mode aware. Cards are
  supplied by the `#card="{ card }"` scoped slot. Drag uses one `VueDraggable` per column sharing
  a single `group`; a cross-column drop fires
  `router.optimistic(...).patch(statusAction(id).url, { status })` with `preserveScroll` +
  `preserveState`, so the card moves instantly and Inertia rolls the props back (plus a
  `vue-sonner` error toast via `onError`) on failure. Same-column drops are a no-op.
  Drag-safety: SortableJS `filter="[data-kanban-ignore]"` with `preventOnFilter: false`; the card
  title `<Link>` and the Deal card's "+ Offer" button both carry `data-kanban-ignore`, so clicking
  them navigates instead of starting a drag.
  - Board pages `pages/{Company,Contact,Deal,Offer}/Board.vue` replace the step-3 stubs, each with
    the spec §3 card content, the `Board | List` toggle (new `components/ViewToggle.vue` — the
    List half renders **disabled** until step 7 adds the list views), and the Offers board's
    "+ New offer" header button. Deal cards link to `offers.create({ query: { deal: id } })`.
  - Supporting files: `types/kanban.ts` (`KanbanColumn`, `KanbanCards<T>`), `types/models.ts`
    (Company/Contact/Deal/Offer/OfferItem prop shapes), `lib/statusColor.ts` (static Tailwind
    class map for the enum `color()` names — never interpolated, so the scanner sees them), and
    `lib/format.ts` (`formatCurrency` → `1.234,56 €`, `formatDate` → `26 Aug 2026`).
    `components/AppSidebar.vue` gained nav links to the four boards (nav is finalized in step 7).
  - **Deviations / notes:** the boards' card props are *not* deferred (step 3 delivers them
    inline and its tests assert that), so the skeleton branch is dormant support for a later
    switch rather than the current path. The card **title** is the click-through link rather than
    the whole card, so the grab surface and the navigation surface never fight. `groupBy` omits
    statuses with no records, so `KanbanBoard` treats a missing group as an empty column.
    No Pest browser test: `pestphp/pest-plugin-browser` is not installed and dependency changes
    need separate approval — the doc marks that test optional.
  - Tests: `tests/Feature/KanbanBoardTest.php` — a dataset asserting all four boards return 200
    with the right component and exactly one column per enum case matching `options()` (value,
    label, color, order), plus a per-entity test that the grouped cards carry the exact fields
    each card renders (`contacts_count`/`deals_count`, `company.name`, `value`,
    `deal.company.name`, `total`), and two tests covering the drag round-trip (a valid `PATCH
    …/status` moves the card between columns after reload; an invalid one 422s and leaves it
    put). `php artisan test --compact`: 88 tests, 87 passed, 1 pre-existing skip.
    `vendor/bin/pint --dirty --format agent`: clean. `npm run build`, `npm run types:check`,
    `npm run lint:check`, `npm run format:check`: all clean.
