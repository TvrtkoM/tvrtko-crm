---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Entity forms: shared partial + FormField/SelectInput
Create/Edit pages are thin: they set Head/breadcrumbs and render the colocated `pages/{Entity}/{Entity}Form.vue` partial, which owns the `useForm` state and submits via `form.submit(record ? update(record.id) : store(), { preserveScroll: true })` using Wayfinder actions.

Build fields with `components/FormField.vue` (label + slot + InputError) and `components/SelectInput.vue`. Do not bind a shadcn/Reka `Select` straight to a nullable id: Reka cannot hold an empty string, so SelectInput maps null through a `__none__` sentinel and casts ids back to numbers with `numeric`.

Dates are native `<input type="date">` via the shadcn `Input`; feed them `toDateInput()` from `lib/format.ts` — Laravel serializes `date` casts as full ISO timestamps, which the control silently rejects.
