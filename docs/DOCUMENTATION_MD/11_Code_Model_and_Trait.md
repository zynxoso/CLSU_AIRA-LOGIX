# Code Reference: Models and Traits (Current)

## Executive Summary
This file documents the database models and shared trait logic used across the current React/Inertia app and Laravel backend.

## 1. Model: IctServiceRequest

File: `app/Models/IctServiceRequest.php`

This is the main record model used by the dashboard, intake, smart scan, edit, report, and export flows.

Fillable fields (21):

- `control_no`
- `timestamp_str`
- `client_feedback_no`
- `name`
- `position`
- `office_unit`
- `contact_no`
- `date_of_request`
- `requested_completion_date`
- `request_type`
- `location_venue`
- `request_description`
- `received_by`
- `receive_date_time`
- `action_taken`
- `recommendation_conclusion`
- `status`
- `date_time_started`
- `date_time_completed`
- `conducted_by`
- `noted_by`

Used by:

- `resources/js/pages/dashboard.tsx`
- `resources/js/pages/intake.tsx`
- `resources/js/pages/smart-scan.tsx`
- `resources/js/pages/requests/edit.tsx`
- `resources/js/components/ict-request-form.tsx`
- `app/Services/IctTemplateService.php`
- `app/Services/LogSyncService.php`

Security note:

- Sensitive fields are encrypted at rest using Eloquent encrypted casts.
- Date fields are cast as `datetime` for consistent handling.

## 2. Model: User

File: `app/Models/User.php`

What it does:

- stores user role and permissions
- supports password history, password age, failed login count, and temporary lockout fields

Roles used in code:

- `super_admin`
- `admin`

## 3. Model: AiUsageLog

File: `app/Models/AiUsageLog.php`

What it does:

- stores AI call usage details such as service, model, tokens, estimated cost, and metadata
- links to a user through `user_id` when available

## 4. Model: IctSearchIndex

File: `app/Models/IctSearchIndex.php`

What it does:

- supports search indexing for request data
- helps speed up lookup and filtering behavior in the dashboard

## 5. Trait: HasAiProvider

File: `app/Traits/HasAiProvider.php`

This trait returns an AI provider object based on selected settings.

Behavior:

- if provider is `gemini`, it returns the Gemini provider with key, model, and temperature
- otherwise it returns the Ollama provider with URL, model, and temperature

Purpose:

- provides one reusable place for runtime provider switching

---

