# Tvrtko CRM — Implementation Log

Running log + index for building the CRM. Full specification: @docs/PROJECT.md

Implement the steps **in sequence**. Each step has its own detailed guide (linked below) and is
self-contained enough to be done and verified on its own. After finishing a step, update its
status here and append any notes/deviations under **Log**.

## Steps

| #   | Step                                | Guide                       | Status         |
| --- | ----------------------------------- | --------------------------- | -------------- |
| 1   | Auth trim + seed DemoUser           | @docs/1_auth_trim.md        | ⬜ Not started |
| 2   | Data layer                          | @docs/2_data_layer.md       | ⬜ Not started |
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

- _(nothing yet)_
