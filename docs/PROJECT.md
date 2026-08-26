# Project - CRM System

A simple CRM system is required. For implementation it is allowed to use all available PHP/Laravel packages, libraries,
and other open-source tools that could help in the realization.

## Required

**Database.** SQLite for local development, as already configured. Production runs on
PostgreSQL (managed by Laravel Cloud — see Deployment). Laravel is database-agnostic, so the
same codebase targets both via the `DB_CONNECTION` env var. The only constraint: keep all
migrations portable — use the standard `Schema` builder and query builder, no vendor-specific
raw SQL — so they run cleanly on both SQLite and Postgres.

Frontend technology - vue (as already configured). UI Library arbitrary.

The app should be deployable and present online as end goal.

Application name should be Tvrtko CRM.

## Deployment

**Goal.** Publish the finished app to a public HTTPS URL so it can be presented to the client
company for a short window (a few days).

**Provider: Laravel Cloud.**

- Most Laravel-native path: `git push` deploy, auto-detects the framework, and runs the
  PHP + Vite/Inertia production build with near-zero config (no Dockerfile).
- Managed **PostgreSQL** provisioned from the dashboard.
- Free HTTPS subdomain (e.g. `tvrtko-crm.laravel.cloud`); custom domains supported.
- **Cost for this demo: effectively $0.** New accounts get a free first month plus $5 in
  usage credit, which covers a few-days demo. (The old perpetual $0 "Sandbox" plan is
  deprecated; the entry plan is now Starter at $5/mo + usage after the first month, so this is
  free for the demo window rather than free forever.)
- **Hibernation (scale-to-zero):** Flex compute sleeps after a configurable idle period
  (default example ~60 min) and wakes on the next request in **under 500 ms** — app, database,
  and cache wake together. Cold starts are effectively imperceptible, so no demo warm-up ritual
  is needed.

**Deployment requirements the app must satisfy:**

- Run migrations on deploy: `php artisan migrate --force`.
- Build frontend assets for production: `npm run build` (handled by Cloud's build step).
- Generated offer PDFs must NOT rely on durable local disk. Prefer generating them on-demand
  per download request (no storage needed); if caching is ever required, use an external
  object store rather than the local filesystem.
- All secrets/config via environment variables (`APP_KEY`, `DB_*`, etc.), set in the Cloud
  dashboard.
- Production env: `APP_ENV=production`, `APP_DEBUG=false`.
- Optional: enable **Inertia SSR** (one toggle in Cloud) if SEO/first-paint matters; not
  required for an internal demo.

## Functionality

### 1. Login

The Laravel starter kit already ships a complete Fortify auth backend (Inertia + Vue):
login, registration, password reset, email verification, two-factor auth (TOTP + recovery
codes), passkeys (WebAuthn), login throttling (5/min per email+IP), and Profile/Security/
Appearance settings pages. Login works. For this CRM demo we trim it down rather than add to it.

**Decisions:**

- **Disable public registration.** An internal CRM should not let anyone self-sign-up. Seed a
  single demo user instead: name `DemoUser`, email `demo@example.com`, password `DemoUser`
  (login is by email in this kit — no username field). `email_verified_at` set so login is
  immediate.
- **No mail.** Not in requirements. This also means **password reset is out** (it depends on
  mail), so disable that feature too rather than ship a dead "Forgot password?" link.
- **Email + password only.** Strip every extra auth feature — email verification, two-factor
  auth (TOTP), and passkeys (WebAuthn) — along with all of their management UI. The demo needs
  a single factor and nothing more.

**Implementation notes (blast radius — do at build time, not now):**

- `config/fortify.php`: reduce the `features` array to empty — remove `registration()`,
  `resetPasswords()`, `emailVerification()`, `twoFactorAuthentication()`, and `passkeys()`.
  (Core email/password login needs no feature flag.)
- `routes/web.php` and `routes/settings.php`: drop the `verified` middleware. In
  `settings.php`, also remove the passkey-specific `.well-known/passkey-endpoints` route. Keep
  the `RequirePassword` guard on the security settings route (still used for password change).
- `database/seeders/DatabaseSeeder.php`: seed the demo user above.
- `app/Models/User.php` (optional cleanup): the `TwoFactorAuthenticatable` +
  `PasskeyAuthenticatable` traits and `implements PasskeyUser` can be removed. The
  `two_factor_*` columns and `passkeys` table are harmless to leave; no need to drop them.
- Removing those features deletes their routes, so the frontend must follow:
    - `pages/auth/Login.vue`: remove the `register` import + "Sign up" link, the
      `request`/"Forgot your password?" block, **and** the `<PasskeyVerify />` component +
      its import (passkey login button).
    - `pages/Welcome.vue`: remove the `register` import + "Register" button.
    - `pages/settings/Security.vue`: rework to drop the two-factor setup and passkey-management
      sections; what remains is the password-change form. Delete the passkey components
      `components/ManagePasskeys.vue`, `components/PasskeyRegister.vue`,
      `components/PasskeyVerify.vue`.
    - Delete now-dead pages `auth/Register.vue`, `auth/ForgotPassword.vue`,
      `auth/ResetPassword.vue`, `auth/VerifyEmail.vue`, `auth/TwoFactorChallenge.vue` (Vite
      globs every page; their imports of the removed routes would break the build). Keep
      `auth/ConfirmPassword.vue` — the `RequirePassword` guard still renders it.
    - `FortifyServiceProvider::configureViews()`: remove the view callbacks for the deleted
      pages (register, reset, forgot, verify-email, two-factor-challenge). Keep `loginView` and
      `confirmPasswordView`.
    - Regenerate Wayfinder, then `npm run build` to confirm no broken imports.
- Update/trim the starter-kit auth tests for the removed features (`RegistrationTest`,
  `PasswordResetTest`, `EmailVerificationTest`, `VerificationNotificationTest`,
  `TwoFactorChallengeTest`; adjust `SecurityTest` to drop 2FA/passkey assertions). Keep
  `AuthenticationTest` and `PasswordConfirmationTest`.
- Production: set `APP_URL` to the real Cloud domain (password-confirm links derive from it).

### 2. Entities - Companies, Contacts, Deals, Offers

Five models: the four CRM entities plus `OfferItem` (offer line items). Money is EUR
throughout — no currency column. All amounts are `decimal(12,2)` columns with a Laravel
`decimal:2` cast (portable across SQLite/Postgres, no float rounding).

**Relationships (a strict top-down hierarchy):**

```
Company ─┬─< Contact
         └─< Deal ─< Offer ─< OfferItem
                 ^
Contact ─────────┘  (a Deal's optional primary contact)
```

- `Company` hasMany `Contact`, hasMany `Deal`
- `Contact` belongsTo `Company`; `Deal` belongsTo one primary `Contact` (optional)
- `Deal` belongsTo `Company` (required), hasMany `Offer`
- `Offer` belongsTo `Deal` (required), hasMany `OfferItem`

**FK on-delete behavior:** `deals.company_id`, `offers.deal_id`, `offer_items.offer_id` →
`cascadeOnDelete` (strict hierarchy). `contacts.company_id`, `deals.contact_id` →
`nullOnDelete` (looser links survive).

#### `companies`

| Column                          | Type   | Notes                                |
| ------------------------------- | ------ | ------------------------------------ |
| name                            | string | required                             |
| email, phone, website, industry | string | nullable                             |
| address, city, country          | string | nullable                             |
| status                          | string | `CompanyStatus` enum, default `Lead` |
| notes                           | text   | nullable                             |

#### `contacts`

| Column                             | Type           | Notes                               |
| ---------------------------------- | -------------- | ----------------------------------- |
| company_id                         | FK → companies | nullable, `nullOnDelete`            |
| first_name                         | string         | required                            |
| last_name, email, phone, job_title | string         | nullable                            |
| status                             | string         | `ContactStatus` enum, default `New` |
| notes                              | text           | nullable                            |

#### `deals`

| Column              | Type           | Notes                                     |
| ------------------- | -------------- | ----------------------------------------- |
| company_id          | FK → companies | required, `cascadeOnDelete`               |
| contact_id          | FK → contacts  | nullable, `nullOnDelete`                  |
| title               | string         | required                                  |
| value               | decimal(12,2)  | nullable — expected deal value (EUR)      |
| expected_close_date | date           | nullable                                  |
| status              | string         | `DealStage` enum, default `Qualification` |
| notes               | text           | nullable                                  |

#### `offers`

| Column       | Type         | Notes                                                          |
| ------------ | ------------ | -------------------------------------------------------------- |
| deal_id      | FK → deals   | required, `cascadeOnDelete`                                    |
| offer_number | string       | unique, auto-generated `OFF-{year}-{0000}` (per-year sequence) |
| title        | string       | nullable                                                       |
| status       | string       | `OfferStatus` enum, default `Draft`                            |
| issue_date   | date         | default today                                                  |
| valid_until  | date         | nullable                                                       |
| tax_rate     | decimal(5,2) | percent, default `25.00` (Croatia PDV)                         |
| notes        | text         | nullable — terms / footer text for the PDF                     |

Totals are **computed accessors**, not stored columns: `subtotal` = Σ `offer_items.line_total`,
`tax_amount` = `subtotal × tax_rate / 100`, `total` = `subtotal + tax_amount`. (If we ever
want an immutable document snapshot, promote these to stored columns — not needed for the demo.)

#### `offer_items`

| Column      | Type          | Notes                       |
| ----------- | ------------- | --------------------------- |
| offer_id    | FK → offers   | required, `cascadeOnDelete` |
| description | string        | required                    |
| quantity    | decimal(10,2) | required, default 1         |
| unit_price  | decimal(12,2) | required (EUR)              |
| position    | integer       | sort order in form + PDF    |

`line_total` (= `quantity × unit_price`) is a computed accessor, not a column.

#### Status enums

Native PHP 8.4 string-backed enums in `App\Enums`, TitleCase cases. Each carries a `label()`
and a `color()` helper for the Kanban columns and badges (used in #3).

| Enum            | Cases                                           |
| --------------- | ----------------------------------------------- |
| `CompanyStatus` | Lead, Prospect, Customer, Inactive              |
| `ContactStatus` | New, Contacted, Qualified, Unresponsive         |
| `DealStage`     | Qualification, Proposal, Negotiation, Won, Lost |
| `OfferStatus`   | Draft, Sent, Accepted, Rejected, Expired        |

#### Conventions & scaffolding

- **Soft deletes: off. Per-user ownership: none** (single seeded `DemoUser`). Timestamps on all.
- `offer_number` generated on create (model `creating` event or a dedicated action), format
  `OFF-2026-0001`, sequence resetting per calendar year.
- One migration per table; standard `Schema` builder only (portable — see Required).
- **Factories + seeders for every model**, wired into `DatabaseSeeder` so the demo opens with
  populated Kanban boards (e.g. a handful of companies, each with contacts + deals, some deals
  carrying offers with line items across the various statuses).
- Eloquent models: relationships, enum casts, `decimal:2` casts, `$fillable`.

### 3. Kanban board for each entity (view by statuses / phases)

Each of the four entities gets its own Kanban board: columns are the entity's status enum
cases, cards are the records, and dragging a card to another column persists the new status.

**Stack decisions:**

- **UI:** the already-installed **shadcn-vue** (`reka-ui` + Tailwind v4) — `card`, `badge`,
  `skeleton`, `vue-sonner` toasts, `@lucide/vue` icons. No new UI library.
- **Drag-and-drop:** **`vue-draggable-plus`** (new dependency — approved). Modern SortableJS
  wrapper, Vue 3 + TS, `v-model` per column, smooth animations.
- **No TanStack Query, no Pinia/Vuex.** Server state is Inertia page props; shared state is
  Inertia shared props + VueUse composables; local state is component `ref`/`useForm`.

**Component design:**

- One generic **`<KanbanBoard>`** component reused by all four boards. Props: `columns`
  (derived from the entity's enum — label + color via the `label()`/`color()` helpers from #2)
  and `cards` grouped by status. A **scoped slot renders each card**, so every entity supplies
  its own card layout while sharing the board/column/drag machinery.
- Columns render **every enum case in order**, each with a colored accent (from `color()`) and
  a live **count badge**. Terminal stages (Deal `Won`/`Lost`, Offer `Rejected`/`Expired`) are
  plain columns with distinct green/red accents — uniform behavior, no special-casing.
- Horizontally scrollable, responsive, dark-mode aware (app already has a theme toggle).
  Empty columns show a muted placeholder; deferred board data shows `skeleton` cards.

**Data & routes:**

- Board page per entity, e.g. `GET /deals/board` → `Inertia::render` with records eager-loaded
  (with their relations) and grouped by status for the columns.
- **Status change on drop:** dedicated lightweight endpoint `PATCH /deals/{deal}/status`
  with `{ status }`, validated against the enum via `Rule::enum(DealStage::class)`. Kept
  separate from the full update/edit endpoint so a drag doesn't run full-form validation.
- **Optimistic UX:** on drop the card moves immediately (Inertia v3 optimistic update); the
  PATCH fires in the background with **automatic rollback** + a `vue-sonner` error toast if it
  fails. This is what makes the drag feel instant.
- **Within-column order is not persisted** — each column sorts by `updated_at desc`, so a
  just-moved card appears at the top. Avoids adding a `position` column per entity. (Cross-
  column drag is the persisted action; the requirement is "draggable between columns".)

**Per-entity card content:**

| Board     | Card shows                                                                          |
| --------- | ----------------------------------------------------------------------------------- |
| Companies | name, industry / city, contact + deal counts                                        |
| Contacts  | full name, job title, company name, email                                           |
| Deals     | title, company, value (€), expected close date; **"+ Offer" quick action (see #5)** |
| Offers    | offer number, deal / company, total (€), valid-until date                           |

- Cards link through to the entity's detail/edit view (#4/#6).
- The Deal card's "+ Offer" shortcut is the hook for #5, detailed there.

### 4. Interface for creation of each entity (forms)

Create **and** edit for all four entities, as **full-page Inertia routes** (matches the starter
kit's settings forms; best for the large Offer form; shareable URLs).

**Structure:**

- Resourceful controllers (`CompanyController`, `ContactController`, `DealController`,
  `OfferController`) with `create/store/edit/update` (+ `index/show` for #6, + the status
  endpoint from #3). Route model binding throughout.
- **One shared form component per entity**, used by both create (empty defaults) and edit
  (record values).
- Submits via **Wayfinder-typed actions** + `useForm`: inline `form.errors` under each field,
  `Spinner` + disabled button while processing.
- **Backend validation via Form Requests** (`StoreDealRequest`/`UpdateDealRequest`…); enum
  fields validated with `Rule::enum(...)`.
- **Inputs:** shadcn `Input` / `Select` / `Label`, plus add shadcn **`Textarea`** (for `notes`).
  Dates (`expected_close_date`, `issue_date`, `valid_until`) as styled native
  `<input type="date">` — avoids pulling in the calendar/popover/date-lib stack for a demo.
- **Relationship pickers** (Contact→Company, Deal→Company/Contact): plain **`Select`**
  (sufficient for seeded demo data; combobox only if lists grow later).

**Prefill niceties:**

- Creating a card from a Kanban column pre-selects that column's **status**.
- Creating an Offer from a Deal card (#5) pre-fills and **locks** `deal_id`.
- Sensible defaults on create: status = first enum case, `issue_date` = today, `tax_rate` = 25,
  one empty line-item row on the Offer form.

**Offer form (the complex one):**

- `deal_id` required (picked via `Select`, or pre-filled from #5).
- **Line-items repeater:** dynamic rows (description / quantity / unit_price) with add & remove
  buttons — **no drag-reorder** (rows keep insertion order via `position`). `useForm` holds an
  `items[]` array; backend validates `items.*.description|quantity|unit_price` and writes
  `OfferItem`s.
- **Live totals in the UI:** per-row `line_total`, then `subtotal → tax (rate field) → total`,
  recomputed as you type — mirrors the server's computed accessors (#2).
- `offer_number` auto-generated on save, shown read-only (not an input).

**Post-save flow:**

- Controller does a server-side `redirect()` + flash success message → surfaced as a
  **`vue-sonner` toast** via one shared `flash` prop wired globally.
- **Create → redirect to the new record's show page** (#6) + toast; the **Offer** show page
  carries the **"Download PDF"** button (#7).
- **Edit → redirect back to the show page** + toast.
- **Validation fails →** 422, `useForm` fills `form.errors`, inline errors, **form retained, no
  redirect**.
- **Cancel →** return to the previous page (board / list / show) without saving.
- **Delete →** redirect to the board (or list) + toast.

Implies a lightweight **per-record show page** per entity (needed anyway for the Offer PDF
button and to show a Company's related contacts/deals) — formalized in #6.

### 5. Shortcut for offer creation on Kanban board

Two entry points, both opening the **full Offer create form** (#4).

**Entry points:**

- **On each Deal card (Deals board):** a small "+ Offer" action (lucide icon). Opens the Offer
  create form with `deal_id` **pre-filled and locked** to that deal.
- **Board-level on the Offers board:** a primary "+ New offer" button in the board header.
  Opens the same form, but with the deal `Select` **empty and editable** — there is no deal
  context to lock, so the user picks the deal.

**Routing:**

- Single create route `GET /offers/create`, optionally `?deal={deal}`. With the param, the
  controller passes the bound `Deal` and the form locks the picker; without it, the picker is
  an editable `Select`. Store is always `POST /offers`. Wayfinder-typed calls: Deal card →
  `create({ deal: deal.id })`, board button → `create()`.
- Optional convenience: default the new offer's `title` from the deal's title.

**Drag-safety:** the "+ Offer" button sits on a draggable card, so exclude it from the drag
(SortableJS `filter` option / a dedicated drag handle) so clicking it never starts a drag.

**Post-save:** follows #4's standard flow — `redirect()` to the **new offer's show page** +
success toast, where the **"Download PDF"** button (#7) is immediately available. Company/
contact context for the offer and its PDF flows through the `deal` relation — nothing is copied
onto the offer.

### 6. Interface for overview of entities

Three surfaces: a per-entity **list (table)** view, a per-record **show** page, and a home
**dashboard**. Each entity page carries a **`Board | List` toggle**, defaulting to **Board**
(#3) — the toggle flips to the table.

#### List (table) view — per entity

- **Plain shadcn-vue `Table`, server-side via Inertia** (no client table library, no new npm
  dependency). Scaffold shadcn-vue's **`table`** and **`pagination`** component files (Vue +
  Tailwind only — not dependencies).
- **Medium feature set**, all server-side through query params on the index route:
  `?search`, `?status`, `?sort`, `?dir`, `?page`. Controller builds the query with `when()`
  guards and returns a Laravel paginator; current filters are passed back so the UI reflects
  state (Inertia `preserveState` on filter changes).
- **Search** box (debounced), **status** filter `Select` (enum cases), **sortable** key columns
  (clickable headers toggling `sort`/`dir`), **pagination** control.

Per-entity columns (sortable ones in **bold**):

| List      | Columns                                                                                 |
| --------- | --------------------------------------------------------------------------------------- |
| Companies | **Name**, Industry, City, **Status**, #Contacts, #Deals, **Created**                    |
| Contacts  | **Name**, **Company**, Job title, Email, **Status**, **Created**                        |
| Deals     | **Title**, Company, Contact, **Value (€)**, **Status**, **Expected close**, **Created** |
| Offers    | **Offer #**, Deal / Company, **Total (€)**, **Status**, **Issue date**, Valid until     |

- Each row has a **row-action `dropdown-menu`**: View → show page, Edit → edit form, Delete
  (see below). The Offers row also exposes **Download PDF** (#7).
- Empty state when no records / no matches.

#### Show (detail) page — per entity

The post-save landing (#4) and the hub for one record: header with status badge + **Edit** /
**Delete** actions, plus inline related lists (un-paginated — fine at demo scale):

- **Company →** its Contacts and its Deals (inline lists) + total pipeline value.
- **Contact →** parent Company + the Deals where it is the primary contact.
- **Deal →** Company, Contact, and its Offers (inline list) + value; the **"+ Offer"** shortcut
  (#5) is available here too.
- **Offer →** parent Deal (→ company/contact), the **line-items table + computed totals**,
  status, issue/valid dates, and the **Download PDF** button (#7).

#### Delete UX

Available in **both** places — the list row menu and the show page. Each triggers a shadcn
**`dialog`** confirmation; on confirm, `DELETE` → redirect to the entity's board (or list) +
success toast. (Respects the FK on-delete rules from #2.)

#### Dashboard (`/dashboard`) — CRM home

Repurpose the existing (currently empty) Fortify landing route into the CRM overview:

- **KPI cards** (shadcn `card`): total Companies, total Contacts, **open Deals** (not
  Won/Lost), **open pipeline value (€)**, offers awaiting response (Sent).
- **Recent activity:** small lists of recent Deals and recent Offers.
- **Quick links** to each entity board + "New" actions.

#### Navigation

Sidebar links to the Dashboard and each entity (landing on its **Board** by default); the
`Board | List` toggle switches views within an entity.

### 7. Offer PDF export

- **Engine: `barryvdh/laravel-dompdf`** (new composer dependency — approved). Pure PHP, no
  system deps → runs on Laravel Cloud. CSS is limited: **table-based layout, inline styles, no
  flex/grid.**
- **On-demand, no storage** (matches the Deployment rule): `GET /offers/{offer}/pdf` renders a
  **Blade template → dompdf → streamed response**. Eager-loads `deal.company`, `deal.contact`,
  and `items`.
- **Delivery: download as attachment** — `Content-Type: application/pdf`,
  `Content-Disposition: attachment`, filename `OFF-2026-0001.pdf` (the `offer_number`).
- **Template contents:**
    - Plain **"Tvrtko CRM"** text header (no logo, no seller/company block).
    - Offer number, issue date, valid-until, status.
    - **Bill-to:** the deal's Company + primary Contact.
    - **Line-items table:** description, quantity, unit price, line total.
    - **Totals:** subtotal → tax (rate %) → total, in **EUR** (formatted e.g. `1.234,56 €`).
    - `notes` rendered as terms / footer text.
- **Reached from:** the **Download PDF** button on the Offer show page (#4/#6) and the Offers
  list row menu (#6).
- Totals reflect current data via the computed accessors (#2); snapshotting is only needed if
  we later require an immutable document.
- **Test:** feature test asserting the route returns `200`, `application/pdf`, the attachment
  filename, and a `%PDF` signature.
