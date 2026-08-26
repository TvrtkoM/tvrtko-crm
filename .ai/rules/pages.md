---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Entity forms: shared partial + FormField/SelectInput
Create/Edit pages are thin: they set Head/breadcrumbs and render the colocated `pages/{Entity}/{Entity}Form.vue` partial, which owns the `useForm` state and submits via `form.submit(record ? update(record.id) : store(), { preserveScroll: true })` using Wayfinder actions.

Build fields with `components/FormField.vue` (label + slot + InputError) and `components/SelectInput.vue`. Do not bind a shadcn/Reka `Select` straight to a nullable id: Reka cannot hold an empty string, so SelectInput maps null through a `__none__` sentinel and casts ids back to numbers with `numeric`.

Dates are native `<input type="date">` via the shadcn `Input`; feed them `toDateInput()` from `lib/format.ts` — Laravel serializes `date` casts as full ISO timestamps, which the control silently rejects.

## List pages: useIndexFilters plus the shared table components
`{Entity}/Index.vue` drives filtering through `composables/useIndexFilters(index, () => props.filters)` — it owns the debounced search ref, the writable `status` computed, `toggleSort`, `goToPage` and `reset`, each issuing a `preserveState`/`preserveScroll`/`replace` visit. Compose the page from `TableFilters`, `SortableHeader`, `StatusBadge`, `RowActions`, `TablePagination` and shadcn `Table`; do not hand-roll query strings.
Keep `ConfirmDeleteDialog` at page level with a `deleting` ref that the row menu sets — a Dialog nested inside `DropdownMenuContent` unmounts with the menu before it can open.
