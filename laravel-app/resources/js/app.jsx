import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const rootElement = document.getElementById('app');

if (rootElement) {
    createInertiaApp({
        resolve: name => {
            const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

            const pagePath = `./Pages/${name}.jsx`;
            const page = pages[pagePath];

            if (!page) {
                console.error(`Page not found: ${pagePath}`, { availablePages: Object.keys(pages) });
                return null;
            }

            return page.default || page;
        },
        setup({ el, App, props }) {
            createRoot(el).render(<App {...props} />);
        },
    });
}
