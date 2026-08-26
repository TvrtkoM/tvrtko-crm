# Step 5 — Kanban frontend

> Spec: @docs/PROJECT.md (§3 Kanban). Progress index: @docs/Log.md
> **Prerequisites:** Step 3 (board + status endpoints). **New dependency:** `vue-draggable-plus`.

**Goal.** A generic, reusable `<KanbanBoard>` component and the four entity board pages, with
drag-between-columns that persists status optimistically.

Activate `inertia-vue-development`, `wayfinder-development`, and `tailwindcss-development` skills.

## Dependency

- `npm i vue-draggable-plus` (approved during planning).

## Generic component — `resources/js/components/KanbanBoard.vue`

- **Props:** `columns` (`[{value,label,color}]` from the enum `options()`), `cards` (grouped by
  status, or grouped internally), and config: the Wayfinder status action, the card id key, the
  status field name.
- **Drag:** `vue-draggable-plus` per column, all columns sharing one `group` so cards move
  between them.
- **Persist on cross-column drop:** call the Wayfinder-typed `PATCH …/status` action with the
  card id + new status, using an **optimistic update** (move immediately) with **automatic
  rollback** + a `vue-sonner` error toast on failure. (No-op if dropped back into the same column.)
- **Card rendering:** a scoped slot `#card="{ card }"` so each board supplies its own card layout.
- **Column header:** colored accent (from `color()`), label, and a **live count badge**. Empty
  columns show a muted placeholder.
- **Layout:** horizontally scrollable, responsive, dark-mode aware. Show `skeleton` cards if the
  board data is delivered as a deferred prop.
- **Drag-safety:** configure SortableJS `filter` (or a dedicated drag handle) so interactive
  buttons on a card — e.g. the Deal card's "+ Offer" — don't start a drag.

## Board pages — `resources/js/pages/{Company,Contact,Deal,Offer}/Board.vue`

- Consume `<KanbanBoard>`, passing the columns (from props) and the card slot with per-entity
  content (spec §3 card table):
  - Companies: name, industry/city, contact + deal counts.
  - Contacts: full name, job title, company, email.
  - Deals: title, company, value (€), expected close date, **"+ Offer"** button (links to the
    offer create form with the deal — form itself lands in step 6).
  - Offers: offer number, deal/company, total (€), valid-until.
- Offers board header: a **"+ New offer"** button.
- Wrap in the authenticated layout; page header includes the **`Board | List`** toggle (the List
  target is added in step 7 — stub/disable that half until then).

## Navigation

- Add sidebar links to each entity board (nav is finalized in step 7).

## Tests

- Pest smoke/browser test (optional but recommended): visit each `/{entity}/board`, assert it
  mounts with no console errors and renders the expected column count. Drag interaction is mostly
  verified manually + by the step-3 `updateStatus` feature test.

## Acceptance criteria

- Each board renders populated from the seed data.
- Dragging a card to another column persists the new status (verify after reload); a forced
  failure rolls back and shows an error toast.
- `npm run build` clean.

## Finish

- `vendor/bin/pint --dirty --format agent` (if PHP touched); mark step 5 ✅ in @docs/Log.md.
