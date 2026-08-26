# Step 7 — Overview: tables + show pages + dashboard

> Spec: @docs/PROJECT.md (§6 overview). Progress index: @docs/Log.md
> **Prerequisites:** Step 3 (controllers), Step 6 (forms + show as the post-save target).
> **New dependencies:** none (scaffold shadcn-vue component files only).

**Goal.** The per-entity **list (table)** view, the per-record **show** page, the **delete** UX,
the **`/dashboard`** CRM home, and the finalized navigation + `Board | List` toggle.

## shadcn-vue components

- Scaffold the **Table** and **Pagination** component files (component files, not npm deps).

## Server-side index (per controller)

- Flesh out `{Entity}Controller@index`: apply `?search`, `?status`, `?sort`, `?dir`, `?page` via
  `when()` guards; **whitelist** sortable columns; return a Laravel paginator plus the current
  filter state and the enum `options()`. Pass pagination meta for the control.

## List pages — `resources/js/pages/{Entity}/Index.vue`

- shadcn `Table` with the per-entity columns (spec §6). Sortable headers issue Inertia visits with
  updated `sort`/`dir` (use `preserveState`/`preserveScroll`). Debounced **search** box and a
  **status** filter `Select`. **Pagination** control.
- Row-action `dropdown-menu`: **View** → show, **Edit** → edit form, **Delete** (below). Offers
  rows also expose **Download PDF** (`offers.pdf`).
- Empty state when there are no records / no matches.

## Show pages — `resources/js/pages/{Entity}/Show.vue`

Header with status badge + **Edit** / **Delete** actions, plus inline related lists
(un-paginated — fine at demo scale):

- **Company →** its Contacts + its Deals + total pipeline value.
- **Contact →** parent Company + the Deals where it is the primary contact.
- **Deal →** Company, Contact, its Offers, value; the **"+ Offer"** shortcut is available here too.
- **Offer →** parent Deal (→ company/contact), the **line-items table + computed totals**, status,
  issue/valid dates, and the **Download PDF** button.

## Delete UX

- In **both** the list row menu and the show page: a shadcn **`dialog`** confirmation → `DELETE`
  → redirect to board/list + success toast. (Honors the FK on-delete rules from §2.)

## Board | List toggle + navigation

- Finalize the toggle on each entity page (Board ↔ Index).
- Sidebar: **Dashboard** + the four entities (each landing on its **Board** by default).

## Dashboard — `/dashboard`

- Replace the stub `Dashboard.vue` with the CRM home. Compute stats in a controller (convert the
  existing `Route::inertia('dashboard', …)` to a controller action, or share via props):
  - **KPI cards:** total Companies, total Contacts, **open Deals** (not Won/Lost), **open pipeline
    value (€)**, offers awaiting response (`Sent`).
  - **Recent activity:** small lists of recent Deals and Offers.
  - **Quick links** to each board + "New" actions.

## Tests (feature)

- Index: search/status/sort/pagination narrow and order results correctly; only whitelisted sort
  columns are honored.
- Show: renders the right component + relations.
- Destroy from both entry points.
- Dashboard: the computed stats match seeded data.
- `php artisan test --compact`.

## Acceptance criteria

- Full navigation works; tables filter/sort/paginate server-side; deletes confirm then redirect;
  the dashboard shows real numbers.
- `npm run build` clean.

## Finish

- `vendor/bin/pint --dirty --format agent`; mark step 7 ✅ in @docs/Log.md.
