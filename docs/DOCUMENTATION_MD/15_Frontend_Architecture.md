# Frontend Architecture

AIRA-LOGIX uses a modern, high-performance frontend stack built on **React 19**, **Inertia.js**, and **Tailwind CSS v4**.

## 1. Core Stack

- **React 19**: Modern component-based UI.
- **Inertia.js v2**: "The Modern Monolith" - allows building SPAs using classic server-side routing and controllers.
- **Tailwind CSS v4**: CSS-first configuration with high performance and modern features like container queries.
- **Vite**: Ultra-fast build tool and dev server.
- **Lucide React**: Icon library for a clean interface.
- **Recharts**: Data visualization for reports.
- **Sonner**: Toast notifications.

## 2. Page Directory Structure

All frontend pages reside in `resources/js/pages/`.

- **`dashboard.tsx`**: Main logs and search table.
- **`intake.tsx`**: Form for manual service requests.
- **`smart-scan.tsx`**: Drag-and-drop area for AI-assisted file extraction.
- **`reports.tsx`**: Analytics charts and CSV/XLSX export controls.
- **`ai-consumption.tsx`**: Admin view for AI spending and logs.
- **`auth/`**: Login, registration, and password recovery pages.
- **`superadmin/`**: User management and high-level system views.

## 3. Shared Components (`resources/js/components/`)

To maintain UI consistency, most functionality is encapsulated in shared components.

- **`ict-request-form.tsx`**: The unified form used for both Intake (creating) and Editing requests.
- **`app-sidebar.tsx`**: Sidebar navigation used across the application shell.
- **`flash-toasts.tsx`**: Global handler for showing success/error messages sent from Laravel.
- **`skeleton-loader.tsx`**: Visual placeholders for loading states.

## 4. State Management and Data Fetching

- **Inertia Props**: Data is passed directly from Laravel controllers as properties to React pages. No separate REST/GraphQL API layer is needed for page rendering.
- **Inertia Forms**: The `useForm` hook handles input state, validation errors, and submission.
- **Ziggy**: Dynamic route URL generation inside JS (`route('ict.index')`).

## 5. Styling and Theming (Tailwind v4)

Tailwind v4 is configured via the Vite plugin. Theme customization is handled in `resources/css/app.css` using the standard Tailwind `@theme` directive.

### Primary Color Palette:
- **Primary**: Slate/Zinc (Professional and clean).
- **Secondary**: Indigo (Highlights and active states).
- **Success/Error**: Emerald and Rose.

### Global Components:
The UI follows a consistent "Dashboard" look:
- Cards for summary metrics.
- Tables with filter and search headers.
- Sidebars for navigation.
- Dialogs for important actions like deletion.

## 6. Developing New UI Features

1. Create a React component in `resources/js/pages/` (e.g., `feature-xyz.tsx`).
2. Add a route in `routes/web.php` returning `Inertia::render('feature-xyz', [...data])`.
3. Use shared UI components from `resources/js/components/` to ensure aesthetic alignment.
4. If the feature requires icons, use `lucide-react`.
5. For complex data visualization, use `recharts`.
