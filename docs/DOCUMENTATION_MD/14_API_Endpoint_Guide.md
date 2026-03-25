# API Endpoint Guide

This document lists the primary web and internal API routes for AIRA-LOGIX, categorized by their feature area.

## 1. Authentication and Global Shell

| Methodist | Route | Controller Method | Description |
|-----------|-------|-------------------|-------------|
| `GET` | `/` | (Redirect) | Redirects based on auth status. |
| `GET` | `/login` | `AuthenticatedSessionController@create` | Standard login page. |
| `POST` | `/login` | `AuthenticatedSessionController@store` | Login attempt. |

## 2. ICT Service Requests (Dashboard & Intake)

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/dashboard` | `DashboardController@index` | Main requests log and search. |
| `GET` | `/dashboard/intake` | `IctServiceRequestController@intake` | Manual intake form. |
| `POST` | `/dashboard/intake` | `IctServiceRequestController@store` | Save a new request. |
| `GET` | `/requests/{id}/edit` | `IctServiceRequestController@edit` | Edit an existing request. |
| `PUT` | `/requests/{id}` | `IctServiceRequestController@update` | Update an existing request. |
| `DELETE` | `/requests/{id}` | `IctServiceRequestController@destroy` | Soft delete a request. |
| `POST` | `/requests/{id}/restore` | `IctServiceRequestController@restore` | Restore a requested deleted record. |

## 3. SMART Scan and AI Extraction

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/dashboard/smart-scan` | `IctServiceRequestController@smartScan` | Main scan interface for uploads. |
| `POST` | `/api/extract` | `IctServiceRequestController@extract` | Upload image/doc for AI extraction. |
| `GET` | `/api/extract/{jobId}/status` | `IctServiceRequestController@checkStatus` | Poll for job status. |
| `POST` | `/api/requests` | `IctServiceRequestController@storeManual` | Save an extracted request. |
| `POST` | `/api/requests/batch` | `IctServiceRequestController@storeBatch` | Save multiple extracted requests. |

## 4. Reports, Analytics, and Budget

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/dashboard/reports` | `AnalyticsController@index` | Charts and performance metrics. |
| `GET` | `/dashboard/ai-consumption` | `AiConsumptionController@index` | Spend tracking and provider logs. |
| `GET` | `/dashboard/export/csv` | `IctServiceRequestController@export` | Export filtered data to CSV. |
| `GET` | `/dashboard/export/xlsx` | `IctServiceRequestController@exportXlsx` | Export filtered data to Excel. |
| `GET` | `/dashboard/export/bulk-docx` | `IctServiceRequestController@exportBulkDocx` | Export filtered records as a ZIP of Word docs. |
| `GET` | `/requests/{id}/download` | `IctServiceRequestController@download` | Download single Word report for a request. |

## 5. Super Admin (Management)

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/superadmin/dashboard` | `SuperAdminDashboardController@index` | High-level system overview. |
| `GET` | `/superadmin/users` | `UserManagementController@index` | List all users and roles. |
| `POST` | `/superadmin/users` | `UserManagementController@store` | Create a new user. |
| `PUT` | `/superadmin/users/{user}` | `UserManagementController@update` | Update user details or role. |
| `DELETE` | `/superadmin/users/{user}` | `UserManagementController@destroy` | Remove a user. |

## 6. Route Protection Summary

- **`auth` Middleware**: All internal routes require a valid session.
- **`can:access-dashboard`**: Gates main request logs and basic search.
- **`can:access-smart-scan`**: Gates AI-powered extraction tools.
- **`role:super_admin`**: Only allows access to the superadmin prefix and user management.
- **CSRF Protection**: All `POST`, `PUT`, `DELETE` requests require a CSRF token (handled by Inertia's Axios setup or X-CSRF-TOKEN header).

## 7. JSON Request Examples (API)

### `POST /api/extract`
```json
{
  "file": (Binary file upload)
}
```
**Response:**
```json
{
    "jobId": "uuid-1234",
    "status": "pending"
}
```

### `GET /api/extract/{jobId}/status`
**Response:**
```json
{
    "status": "completed",
    "data": {
        "control_no": "2025-001",
        "name": "Juan Dela Cruz",
        "request_type": "Hardware Repair"
    }
}
```
