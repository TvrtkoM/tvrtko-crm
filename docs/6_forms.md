# Step 6 — Forms (create/edit + offer shortcut)

> Spec: @docs/PROJECT.md (§4 forms, §5 offer shortcut). Progress index: @docs/Log.md
> **Prerequisites:** Step 3 (controllers + Form Requests), Step 5 (boards providing the entry
> points). **New dependencies:** none (scaffold a shadcn-vue component file only).

**Goal.** Full-page create/edit forms for all four entities, the Offer line-items form, and the
"+ Offer" shortcut wiring.

Activate `inertia-vue-development` and `wayfinder-development` skills.

## shadcn-vue component

- Scaffold the **Textarea** component file (for `notes`) — a component file, **not** an npm
  dependency. Follow the existing `resources/js/components/ui/*` convention.

## Shared form per entity

- `resources/js/pages/{Entity}/Create.vue` and `Edit.vue`, each rendering a shared
  `{Entity}Form.vue` partial (create = empty defaults, edit = record values).
- `useForm` + Wayfinder-typed `store`/`update` actions; inline `form.errors` under each field;
  `Spinner` + disabled submit while `form.processing`.
- Inputs: shadcn `Input` / `Select` / `Label` / `Textarea`; dates as styled native
  `<input type="date">`. Relationship pickers (Contact→Company, Deal→Company/Contact) as plain
  `Select` fed by options passed from the controller.
- **Prefill niceties:** read `?status` to preselect a Kanban column's status; defaults on create
  (status = first enum case, `issue_date` = today, `tax_rate` = 25).

## Offer form (the complex one)

- `deal_id` as a `Select`. If the controller passed a bound `Deal` (from `?deal`), **prefill and
  lock** it (disabled Select + hidden field); otherwise it is editable.
- **Line-items repeater:** an `items[]` array in `useForm`; add/remove row buttons
  (description / quantity / unit_price). **No drag-reorder**; keep `position` = row index.
- **Live totals (client-side computed):** per-row `line_total`, then `subtotal → tax (from the
  `tax_rate` field) → total`, recomputed as the user types, EUR-formatted. Mirrors the server
  accessors (§2).
- `offer_number` is not an input (server-generated).

## "+ Offer" shortcut (§5)

- Deal card "+ Offer" (step 5 button) → `offers.create` with `{ deal: deal.id }` → form has the
  deal locked.
- Offers board "+ New offer" → `offers.create` (no deal) → editable deal picker.

## Post-save flow + flash toast

- Every create/update controller `redirect()`s to the record's **show page** (built in step 7) +
  a flash success message. Until step 7 exists, target the board temporarily, then switch to the
  show route.
- Ensure a shared Inertia **`flash`** prop is shared from `HandleInertiaRequests` and surfaced as
  a global `vue-sonner` toast in the layout (add it if the starter kit doesn't already).

## Tests (feature)

- Create/edit happy path (redirect + DB state) for each entity.
- Validation errors surface (422) and the form is retained.
- Offer create writes the `OfferItem`s and generates the number; `?deal` locks the deal.
- `php artisan test --compact`.

## Acceptance criteria

- Every entity can be created and edited; the offer shortcut works from both entry points and
  lands on the offer show page after save.
- `npm run build` clean.

## Finish

- `vendor/bin/pint --dirty --format agent`; mark step 6 ✅ in @docs/Log.md.
