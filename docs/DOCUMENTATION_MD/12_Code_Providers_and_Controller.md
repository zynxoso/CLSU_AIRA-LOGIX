# Code Reference: Providers, Middleware, and Controller (Current)

## Executive Summary
This file covers the service providers, middleware, and controllers that connect the React/Inertia frontend to the Laravel backend.

## 1. AppServiceProvider

File: `app/Providers/AppServiceProvider.php`

Current behavior:

- `register()` is empty
- `boot()` defines authorization gates:
  - `manage-admins`: Super Admin only
  - `access-dashboard`: requires `dashboard` permission
  - `access-documentation`: requires `documentation` permission
  - `access-smart-scan`: requires `smart_scan` permission

Meaning:

- Controllers and route guards rely on these gates to authorize access.

## 2. HandleInertiaRequests

File: `app/Http/Middleware/HandleInertiaRequests.php`

Current behavior:

- provides the Inertia root template
- shares common data with React pages
- keeps asset versioning aligned with Laravel

## 3. Middleware (Access and Security)

### RoleMiddleware

File: `app/Http/Middleware/RoleMiddleware.php`

Purpose:

- protects admin-only pages such as super admin routes

### SecurityHeaders

File: `app/Http/Middleware/SecurityHeaders.php`

Purpose:

- adds security headers such as CSP and X-Frame-Options
- allows Gemini requests to `https://generativelanguage.googleapis.com`

## 4. Controllers

### DashboardController

File: `app/Http/Controllers/DashboardController.php`

Purpose:

- serves the dashboard page and record data

### IctServiceRequestController

File: `app/Http/Controllers/IctServiceRequestController.php`

Purpose:

- handles intake, smart scan, extraction, save, batch import, and export endpoints

### DocumentationController

File: `app/Http/Controllers/DocumentationController.php`

Purpose:

- serves the documentation page

### AnalyticsController

File: `app/Http/Controllers/AnalyticsController.php`

Purpose:

- provides reports and analytics data

### AiConsumptionController

File: `app/Http/Controllers/AiConsumptionController.php`

Purpose:

- shows AI token and cost consumption

### SuperAdminDashboardController

File: `app/Http/Controllers/SuperAdminDashboardController.php`

Purpose:

- provides the super admin dashboard summary

### Admin\UserManagementController

File: `app/Http/Controllers/Admin/UserManagementController.php`

Purpose:

- creates, updates, and deletes admin users

## 5. Base Controller

File: `app/Http/Controllers/Controller.php`

Current usage:

- shared base controller for the application controller tree

---


