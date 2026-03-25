# Tools and File Index (Current)

## 1. Core tools used in this project

### App framework

- Laravel
- React
- Inertia.js

### AI and extraction

- Gemini API (image)
- Gemini API (text parsing)
- NeuronAI provider helper exists for optional provider switching

### File handling

- `phpoffice/phpword` for reading and filling Word docs
- `phpoffice/phpspreadsheet` for reading and writing spreadsheet files

### Queue and cache

- Laravel queue jobs for long actions
- Laravel cache for job status polling

## 2. Current file index

### Routes and root files

- `routes/web.php`
- `routes/auth.php`
- `resources/js/app.tsx`
- `resources/views/app.blade.php`
- `app/Http/Middleware/HandleInertiaRequests.php`

### React pages

- `resources/js/pages/dashboard.tsx`
- `resources/js/pages/intake.tsx`
- `resources/js/pages/smart-scan.tsx`
- `resources/js/pages/documentation.tsx`
- `resources/js/pages/reports.tsx`
- `resources/js/pages/ai-consumption.tsx`
- `resources/js/pages/requests/edit.tsx`
- `resources/js/pages/auth/login.tsx`
- `resources/js/pages/auth/register.tsx`
- `resources/js/pages/auth/forgot-password.tsx`
- `resources/js/pages/auth/reset-password.tsx`
- `resources/js/pages/auth/confirm-password.tsx`
- `resources/js/pages/auth/verify-email.tsx`
- `resources/js/pages/superadmin/dashboard.tsx`
- `resources/js/pages/superadmin/user-management.tsx`

### Shared React components

- `resources/js/components/ict-request-form.tsx`
- `resources/js/components/snap-to-log-banner.tsx`
- `resources/js/components/app-layout.tsx`
- `resources/js/components/app-sidebar.tsx`
- `resources/js/components/nav-main.tsx`
- `resources/js/components/nav-user.tsx`
- `resources/js/components/flash-toasts.tsx`
- `resources/js/components/breadcrumbs.tsx`

### Jobs

- `app/Jobs/PerformExtractionJob.php`
- `app/Jobs/GenerateExportJob.php`

### Services

- `app/Services/IctExtractionService.php`
- `app/Services/IctImageExtractionService.php`
- `app/Services/IctTemplateService.php`
- `app/Services/LogSyncService.php`
- `app/Services/AiBudgetManager.php`
- `app/Services/Extraction/AiParserService.php`
- `app/Services/Extraction/AiVisionService.php`
- `app/Services/Extraction/SpreadsheetExtractor.php`
- `app/Services/Extraction/DocxTextExtractor.php`
- `app/Services/Extraction/DocxCheckboxExtractor.php`
- `app/Services/Extraction/ImageOptimizer.php`

### Models and helper trait

- `app/Models/IctServiceRequest.php`
- `app/Models/User.php`
- `app/Models/AiUsageLog.php`
- `app/Models/IctSearchIndex.php`
- `app/Traits/HasAiProvider.php`

### Controllers

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/IctServiceRequestController.php`
- `app/Http/Controllers/DocumentationController.php`
- `app/Http/Controllers/AnalyticsController.php`
- `app/Http/Controllers/AiConsumptionController.php`
- `app/Http/Controllers/SuperAdminDashboardController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`

### Middleware and providers

- `app/Providers/AppServiceProvider.php`
- `app/Http/Middleware/RoleMiddleware.php`
- `app/Http/Middleware/SecurityHeaders.php`

### Documentation Index

- `docs/DOCUMENTATION_MD/00_Installation_Guide.md`
- `docs/DOCUMENTATION_MD/01_Technical_Documentation.md`
- `docs/DOCUMENTATION_MD/02_Architecture_Reference.md`
- `docs/DOCUMENTATION_MD/03_Form_Automation_Flow.md`
- `docs/DOCUMENTATION_MD/13_AI_Orchestration_Reference.md`
- `docs/DOCUMENTATION_MD/14_API_Endpoint_Guide.md`
- `docs/DOCUMENTATION_MD/15_Frontend_Architecture.md`

## 3. Which file handles what

- React bootstrap and Inertia setup:
  - `resources/js/app.tsx`
- Page routing and rendering:
  - `app/Http/Controllers/IctServiceRequestController.php`
  - `app/Http/Controllers/DashboardController.php`
- Upload intake and extraction start:
  - `resources/js/pages/intake.tsx`
  - `resources/js/pages/smart-scan.tsx`
  - `app/Http/Controllers/IctServiceRequestController.php`
- Extraction in background:
  - `app/Jobs/PerformExtractionJob.php`
- Document and spreadsheet extraction logic:
  - `app/Services/IctExtractionService.php`
- Image extraction logic:
  - `app/Services/IctImageExtractionService.php`
- Save, search, inline edit, export controls:
  - `resources/js/pages/dashboard.tsx`
  - `app/Http/Controllers/IctServiceRequestController.php`
- Export in background:
  - `app/Jobs/GenerateExportJob.php`
- XLSX/CSV output:
  - `app/Services/LogSyncService.php`
- DOCX and ZIP output:
  - `app/Services/IctTemplateService.php`

## 4. Current data fields

The model currently allows 21 fillable fields in `IctServiceRequest`.

Main field groups:

- identifiers: `control_no`, `timestamp_str`, `client_feedback_no`
- requester: `name`, `position`, `office_unit`, `contact_no`
- request: `date_of_request`, `requested_completion_date`, `request_type`, `location_venue`, `request_description`
- handling: `received_by`, `receive_date_time`, `action_taken`, `recommendation_conclusion`, `status`
- completion: `date_time_started`, `date_time_completed`, `conducted_by`, `noted_by`

## 5. Notes

- Older docs referenced removed frontend pages. Those are no longer the active implementation.
- The current file naming follows React page folders and controller/service class naming.
