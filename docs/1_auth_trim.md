# Step 1 — Auth trim + seed DemoUser

> Spec: @docs/PROJECT.md (§1 Login). Progress index: @docs/Log.md
> **Prerequisites:** none — this is the first step. **New dependencies:** none.

**Goal.** Reduce the Fortify starter-kit auth to plain **email + password** login against one
seeded user. Remove registration, password reset, email verification, two-factor auth, and
passkeys — including their frontend pages/components and the associated tests.

Activate the `fortify-development` skill while working this step.

## Backend

1. **`config/fortify.php`** — set `'features' => []` (remove `registration()`,
   `resetPasswords()`, `emailVerification()`, `twoFactorAuthentication()`, `passkeys()`). The
   `login` rate limiter stays; the `two-factor`/`passkeys` limiter entries and the `passkeys`
   config block can be removed.
2. **`routes/web.php`** — remove `'verified'` from the dashboard middleware group (keep
   `'auth'`).
3. **`routes/settings.php`** — remove `'verified'` from the middleware groups; remove the
   `.well-known/passkey-endpoints` route. **Keep** the `RequirePassword` guard on
   `settings/security` (still used for the password-change flow → renders `ConfirmPassword.vue`).
4. **`app/Providers/FortifyServiceProvider.php`** — in `configureViews()` remove the view
   callbacks for `registerView`, `resetPasswordView`, `requestPasswordResetLinkView`,
   `verifyEmailView`, `twoFactorChallengeView`. Keep `loginView` (drop the `canResetPassword`
   prop, or pass `false`) and `confirmPasswordView`. Remove the `resetUserPasswordsUsing(...)`
   binding and the now-unused `app/Actions/Fortify/ResetUserPassword.php`. Keep
   `createUsersUsing(...)` (used by the seeder path / not exposed via a route).
5. **`app/Models/User.php`** (optional cleanup) — remove the `TwoFactorAuthenticatable` and
   `PasskeyAuthenticatable` traits and `implements PasskeyUser`. The `two_factor_*` columns and
   `passkeys` table are harmless to leave; do **not** write a migration to drop them.
6. **`database/seeders/DatabaseSeeder.php`** — replace the starter "Test User" with the demo
   user: `name = 'DemoUser'`, `email = 'demo@example.com'`, `password = Hash::make('DemoUser')`,
   `email_verified_at = now()`. (Data-model seeding is added in step 2.)

## Frontend

7. **`resources/js/pages/auth/Login.vue`** — remove: the `register` import + "Sign up" block;
   the `request` (`@/routes/password`) import + "Forgot your password?" block (and the
   `canResetPassword` prop usage); the `<PasskeyVerify />` component + its import.
8. **`resources/js/pages/Welcome.vue`** — remove the `register` import + "Register" button (keep
   the login / dashboard links).
9. **`resources/js/pages/settings/Security.vue`** — remove the two-factor-setup and
   passkey-management sections; what remains is the password-change form. Clean up imports.
10. **Delete passkey components:** `resources/js/components/ManagePasskeys.vue`,
    `PasskeyRegister.vue`, `PasskeyVerify.vue`.
11. **Delete dead auth pages:** `resources/js/pages/auth/Register.vue`, `ForgotPassword.vue`,
    `ResetPassword.vue`, `VerifyEmail.vue`, `TwoFactorChallenge.vue`. **Keep**
    `auth/ConfirmPassword.vue`.

## Regenerate & verify

12. `php artisan wayfinder:generate` — removed routes drop out of `@/routes` and `@/actions`.
13. `npm run build` — must succeed with **no missing-import errors**. Chase down any lingering
    references the steps above missed.

## Tests

14. Trim the starter-kit auth tests for removed features: `RegistrationTest`, `PasswordResetTest`,
    `EmailVerificationTest`, `VerificationNotificationTest`, `TwoFactorChallengeTest`, and the
    2FA/passkey assertions in `SecurityTest`. **Deleting tests needs approval** (project rule) —
    confirm first; the fallback is to rewrite them to assert the routes are gone. Keep
    `AuthenticationTest` and `PasswordConfirmationTest`.
15. Add a test asserting `GET /register` returns 404 and that the seeded DemoUser can log in and
    reach `/dashboard`.
16. `php artisan test --compact`.

## Acceptance criteria

- `php artisan test` green; `npm run build` clean.
- Logging in with `demo@example.com` / `DemoUser` lands on `/dashboard`.
- `php artisan route:list` shows **no** `register`, `forgot-password`, `reset-password`,
  `two-factor-*`, or passkey routes; `login`, `logout`, `user/confirm-password`, and the
  settings routes remain.

## Finish

- `vendor/bin/pint --dirty --format agent`.
- Mark step 1 ✅ in @docs/Log.md and note anything non-obvious.
