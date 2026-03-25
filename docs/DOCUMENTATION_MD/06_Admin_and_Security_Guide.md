# Admin and Security Guide (Current)

This document covers the newer admin and security features in AIRA-LOGIX.

## 1. Roles

The app uses two roles:

- `admin`: normal staff user
- `super_admin`: can manage admins and view AI usage

Admin-only access is enforced using:

- Route middleware: `role:super_admin` (see `app/Http/Middleware/RoleMiddleware.php`)
- Gates/permissions: defined in `app/Providers/AppServiceProvider.php`

## 2. Permissions (Admin Users)

Admin permissions are stored as a list on the user record.

Common permissions:

- `dashboard`: access `/dashboard` and record pages
- `documentation`: access `/dashboard/documentation`
- `smart_scan`: access intake/extraction features

Pages call `$this->authorize(...)` and gates check if the user has the needed permission.

## 3. User Management (Super Admin)

Route: `/superadmin/users`

What you can do:

- Create admin users
- Edit admin users (including password reset)
- Assign permissions
- Delete admin users (you cannot delete yourself)

Note:
- The UI manages `admin` accounts only. Super Admin accounts are created via seeders.

## 4. AI Usage Dashboard (Super Admin)

Route: `/dashboard/ai-consumption`

What it shows:

- Total estimated cost and total tokens
- Counts for Gemini vision calls and Gemini text calls
- A paginated table of AI usage logs (`ai_usage_logs`)

## 5. AI Budget Threshold

The Gemini budget threshold is configured via:

- `AI_BUDGET_THRESHOLD` in `.env` (defaults to 10.00 if not set)

Behavior:

- If the monthly threshold is exceeded, new AI extraction calls can be blocked.
- Super Admin users can be notified when the budget is exceeded.

## 6. Password Security (Login + Sidebar)

Features included:

- Login throttling (limits repeated attempts)
- Temporary lockout after too many failed attempts
- Password change support in the sidebar
- Password history check (prevents reusing one of the last passwords)

## 7. Data Protection (Encryption at Rest)

Some fields in `IctServiceRequest` and `User` are encrypted at rest using Eloquent `encrypted` casts.

Important impact:

- Encrypted fields cannot be safely used as dedupe/search keys in code.
- This is why spreadsheet import avoids deduping by `name`.

## 8. Export Guardrails

To keep exports fast and reduce abuse:

- Export cap: 500 records per request
- Export rate limit: 2 attempts per minute per user/IP

