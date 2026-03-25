# Code Reference: React and Inertia Pages (Current)

## Executive Summary
This file maps the current frontend implementation in `resources/js`. The app uses React pages rendered through Inertia.js, with Laravel controllers returning page components and shared UI components handling most form behavior.

## 1. Page Files

- `resources/js/pages/dashboard.tsx` - main service request dashboard
- `resources/js/pages/intake.tsx` - file intake and review flow
- `resources/js/pages/smart-scan.tsx` - drag-and-drop extraction flow
- `resources/js/pages/documentation.tsx` - docs viewer
- `resources/js/pages/reports.tsx` - reports page
- `resources/js/pages/ai-consumption.tsx` - AI usage page
- `resources/js/pages/requests/edit.tsx` - request edit screen
- `resources/js/pages/superadmin/dashboard.tsx` - super admin overview
- `resources/js/pages/superadmin/user-management.tsx` - user management screen
- `resources/js/pages/auth/login.tsx` - login screen
- `resources/js/pages/auth/register.tsx` - registration screen
- `resources/js/pages/settings/profile.tsx` - profile settings
- `resources/js/pages/settings/password.tsx` - password settings
- `resources/js/pages/settings/appearance.tsx` - appearance settings

## 2. Shared Components

- `resources/js/components/ict-request-form.tsx` - shared create/edit request form
- `resources/js/components/snap-to-log-banner.tsx` - intake upload banner and extractor trigger
- `resources/js/components/app-layout.tsx` - page shell and layout wrapper
- `resources/js/components/app-sidebar.tsx` - sidebar navigation
- `resources/js/components/nav-main.tsx` - primary nav links
- `resources/js/components/nav-user.tsx` - user menu actions
- `resources/js/components/flash-toasts.tsx` - toast display for flash messages

## 3. Root Integration Files

- `resources/js/app.tsx` - Inertia app bootstrap and theme/toast setup
- `resources/views/app.blade.php` - Blade root view for Inertia
- `app/Http/Middleware/HandleInertiaRequests.php` - shared Inertia props and root view

## 4. File Naming Pattern

- Pages use lowercase kebab-case filenames that match the route purpose.
- Nested route groups use folders, such as `auth/`, `settings/`, `requests/`, and `superadmin/`.
- Shared components use descriptive lowercase kebab-case names.
- Backend controller names remain PascalCase in `app/Http/Controllers`.

## 5. Route to Page Mapping

- `/dashboard` -> `resources/js/pages/dashboard.tsx`
- `/dashboard/intake` -> `resources/js/pages/intake.tsx`
- `/dashboard/smart-scan` -> `resources/js/pages/smart-scan.tsx`
- `/dashboard/documentation` -> `resources/js/pages/documentation.tsx`
- `/dashboard/reports` -> `resources/js/pages/reports.tsx`
- `/dashboard/ai-consumption` -> `resources/js/pages/ai-consumption.tsx`
- `/requests/{id}/edit` -> `resources/js/pages/requests/edit.tsx`
- `/superadmin/dashboard` -> `resources/js/pages/superadmin/dashboard.tsx`
- `/superadmin/users` -> `resources/js/pages/superadmin/user-management.tsx`

## 6. Notes

- The old component names are no longer the active frontend reference for this project.
- When documenting a feature, cite the React page file, the controller, and the service or job together.

