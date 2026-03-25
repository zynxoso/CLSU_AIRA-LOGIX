import '../css/app.css';
import '../css/no-scrollbar.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';
import { ErrorBoundary } from './components/ErrorBoundary';
import { route as routeFn } from 'ziggy-js';
import { FlashToasts } from './components/flash-toasts';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        const flash = (props as { initialPage?: { props?: { flash?: { success?: string; error?: string } } } }).initialPage?.props?.flash;

        root.render(
            <ErrorBoundary>
                <App {...props} />
                <FlashToasts flash={flash} />
                <Toaster richColors position="top-right" closeButton />
            </ErrorBoundary>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
