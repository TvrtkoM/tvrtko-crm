# Step 3 — Backend for Kanban boards

> Spec: @docs/PROJECT.md (§3 Kanban, §4 forms backend). Progress index: @docs/Log.md
> **Prerequisites:** Step 2. **New dependencies:** none.

**Goal.** Build the server-side surface for all four entities — resourceful controllers, Form
Requests, routes (including the **board data** endpoints and the **status PATCH** endpoint). The
Vue pages these render come in steps 5–7; here we render Inertia components (which can be stubs
until then) and test backend behavior directly.

## Controllers — `php artisan make:controller {Entity}Controller --resource`

For `Company`, `Contact`, `Deal`, `Offer`. Methods:

- `index` — list for the table view (§6). Can start minimal; the full server-side
  search/sort/filter/paginate is refined in step 7.
- `create` / `store` / `edit` / `update` — the forms (frontend in step 6). `store`/`update` type-
  hint the Form Requests below.
- `show` — detail (frontend in step 7).
- `destroy` — delete → `redirect()` back to board/list with a flash success message.
- **`board`** — `Inertia::render('{Entity}/Board', [...])` with records eager-loaded (their
  relations for card content) and the enum `options()` for columns. Group by status server-side
  or return flat + group in the component.
- **`updateStatus`** — handles `PATCH /{entity}/{model}/status`: validate `status` with
  `Rule::enum(...)`, save, return a redirect back / `204` (kept lightweight for optimistic UX;
  no full-form validation).

`OfferController@store` creates the Offer **and** its `OfferItem`s from the `items[]` payload,
triggering `offer_number` generation; `OfferController@create` honors an optional `?deal` param
(pass the bound `Deal` so the form can lock it — see §5, wired in step 6).

## Form Requests — `php artisan make:request {Store,Update}{Entity}Request`

Rules per spec §2: required/nullable, `email`, `url` (website), `numeric`/`min:0` (value, quantity,
unit_price, tax_rate), `date` (dates), `Rule::enum(...)` (status). Offer requests: `deal_id`
required + `exists`, nested `items` array with `items.*.description|quantity|unit_price` rules.
Put `authorize()` → `true` (single seeded user; no policies for the demo).

## Routes — `routes/web.php` (all behind `auth`)

- `Route::resource('companies', CompanyController::class)` (+ contacts, deals, offers).
- Extra named routes **before** the resource (or constrain bindings) so `/{entity}/board` is not
  swallowed by the resource `show` `{model}` param:
  - `GET /{entity}/board` → `board`, name `{entity}.board`
  - `PATCH /{entity}/{model}/status` → `updateStatus`, name `{entity}.status`
  - `GET /offers/{offer}/pdf` → the PDF route (added in step 4; reserve the name `offers.pdf`).
- Use `->whereNumber('model')` or route ordering to avoid `board` colliding with `show`.

## Wayfinder

- `php artisan wayfinder:generate` so typed actions exist for steps 5–7.

## Tests (feature)

- Guests are redirected (auth guard) on every route.
- `store`/`update`: 422 on invalid input; success persists + redirects with flash.
- `updateStatus`: changes the status; rejects a value outside the enum.
- `board`: asserts the correct Inertia component + the expected props (records grouped, column
  options) via Inertia testing helpers.
- `destroy`: deletes and honors the cascade/null rules from §2.
- `OfferController@store`: writes items and generates a unique `offer_number`.
- `php artisan test --compact`.

## Acceptance criteria

- All routes present in `php artisan route:list`; tests green.
- Dragging is not wired yet, but a manual `PATCH …/status` (e.g. via test) moves a record.

## Finish

- `vendor/bin/pint --dirty --format agent`; mark step 3 ✅ in @docs/Log.md.
