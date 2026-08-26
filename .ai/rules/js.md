---
paths:
  - 'resources/js/**'
---

# Js

## Regenerate Wayfinder with --with-form
`vite.config.ts` configures the Wayfinder plugin with `formVariants: true`. Running a bare `php artisan wayfinder:generate` regenerates the actions *without* the `.form()` helpers, and `npm run types:check` then fails in the starter-kit auth/settings pages that use them. Always run `php artisan wayfinder:generate --with-form`.
