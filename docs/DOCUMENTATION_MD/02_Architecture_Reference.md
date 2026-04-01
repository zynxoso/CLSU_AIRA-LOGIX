# AIRA-LOGIX Architecture Reference

## Executive Summary
AIRA-LOGIX uses a Laravel backend with a React frontend delivered through Inertia.js. The browser renders pages from `resources/js`, Laravel controllers coordinate requests, services perform extraction and export work, and jobs handle long-running tasks.

## 1. The Three Layers

- **Presentation**: React pages, layouts, and shared components in `resources/js/pages`, `resources/js/layouts`, and `resources/js/components`.
- **Application**: Controllers, middleware, providers, jobs, and services in `app/Http`, `app/Jobs`, `app/Services`, `app/Providers`, and `app/Http/Middleware`.
- **Persistence**: Eloquent models, migrations, cache, and storage.

## 2. Component Roles

- **Dashboard page**: `resources/js/pages/dashboard.tsx` handles listing, filtering, and actions.
- **Intake page**: `resources/js/pages/intake.tsx` handles upload and review.
- **Smart Scan page**: `resources/js/pages/smart-scan.tsx` handles direct extraction from the browser.
- **Request form component**: `resources/js/components/ict-request-form.tsx` is the shared create/edit form.
- **Documentation page**: `resources/js/pages/documentation.tsx` reads the markdown docs.
- **Super Admin pages**: `resources/js/pages/superadmin/dashboard.tsx` and `resources/js/pages/superadmin/user-management.tsx` manage admin tools.

## 4. AI Orchestration Layer

The AI logic is decoupled from the UI and Controllers via the **Orchestration Layer**:
- **Gateway**: `app/Services/AiOrchestrator.php` handles all model calls.
- **Enforcement**: `app/Services/AiBudgetManager.php` prevents overspending.
- **Extraction**: Specialized services in `app/Services/Extraction/` handle file-specific parsing (PDF, DOCX, XLSX).

Refer to [13_AI_Orchestration_Reference.md](./13_AI_Orchestration_Reference.md) for more.

## 3. Communication Flow

```mermaid
flowchart LR
A[User] --> B[React page]
B --> C[Laravel controller]
C --> D[Service or job]
D --> E[Database / cache / storage]
E --> C
C --> B
```

## 6. Data Flow Diagram (DFD)

```mermaid
flowchart TD
    %% ── External Entities ────────────────────────────────────
    Staff["MISO Staff"]
    Admin["Admin"]

    %% ── Data Stores ──────────────────────────────────────────
    D1[("D1\nService Request DB")]
    D2[("D2\nReport History")]
    D3[("D3\nUser & Auth DB")]

    %% ── Processes ────────────────────────────────────────────
    P1["1.0\nLogin &\nAuthentication"]
    P2["2.0\nSmart Scan\nUpload"]
    P3["3.0\nFile Type\nDetection"]
    P4["4.0\nPhpOffice\nParser"]
    P5["5.0\nOCR & AI\nProcessing"]
    P6["6.0\nVerification\nScreen"]
    P7["7.0\nReport\nGenerator"]
    P8["8.0\nAnalytics\nDashboard"]
    P9["9.0\nUser\nManagement"]

    %% ── Data Flows ───────────────────────────────────────────
    Staff -->|Credentials| P1
    P1 -->|Verify account| D3
    D3 -->|Account verified| P1
    P1 -->|Session granted| Staff

    Staff -->|Upload form file| P2
    P2 -->|Route to detector| P3
    P3 -->|"DOCX / XLSX"| P4
    P3 -->|"Image / Scan"| P5
    P4 -->|Extracted fields| P6
    P5 -->|Normalized data| P6
    P6 -->|Confirmed record| D1

    Staff -->|Select filters| P7
    D1 -->|Retrieved records| P7
    P7 -->|Generated file| D2
    D2 -->|"XLSX / DOCX report"| Staff

    D1 -->|Aggregated data| P8
    P8 -->|Charts & trends| Staff

    Admin -->|Credentials| P1
    Admin -->|Manage users| P9
    P9 -->|Update user records| D3
    D3 -->|User data| P9

    %% ── Styles ───────────────────────────────────────────────
    classDef process fill:#fffde7,stroke:#f9a825,stroke-width:2px,color:#1a1a1a
    classDef store   fill:#f1f8e9,stroke:#558b2f,stroke-width:2px,color:#1a1a1a
    classDef staff   fill:#e8f5e9,stroke:#2e7d32,stroke-width:3px,color:#1a1a1a,font-weight:bold
    classDef admin   fill:#fce4ec,stroke:#880e4f,stroke-width:3px,color:#1a1a1a,font-weight:bold

    class P1,P2,P3,P4,P5,P6,P7,P8,P9 process
    class D1,D2,D3 store
    class Staff staff
    class Admin admin
```

## 4. Key Rules of the House

- React is the UI layer.
- Inertia bridges the frontend and Laravel controllers.
- Heavy extraction and export tasks should run in jobs.
- Authentication and permissions gate access to internal pages.
- Sensitive request fields are encrypted at rest.
- AI usage is tracked and budget-limited.

## 5. Finding Your Way Around

- `/login`: `resources/js/pages/auth/login.tsx`
- `/dashboard`: `resources/js/pages/dashboard.tsx`
- `/dashboard/intake`: `resources/js/pages/intake.tsx`
- `/dashboard/smart-scan`: `resources/js/pages/smart-scan.tsx`
- `/dashboard/documentation`: `resources/js/pages/documentation.tsx`
- `/dashboard/reports`: `resources/js/pages/reports.tsx`
- `/dashboard/ai-consumption`: `resources/js/pages/ai-consumption.tsx`
- `/requests/{id}/edit`: `resources/js/pages/requests/edit.tsx`
- `/superadmin/dashboard`: `resources/js/pages/superadmin/dashboard.tsx`
- `/superadmin/users`: `resources/js/pages/superadmin/user-management.tsx`

---
