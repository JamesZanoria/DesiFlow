import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

        // Vite glob keys are relative to the file they are called in.
        // Since app.jsx is in resources/js/, the keys will look like './Pages/Manager/Orders.jsx'
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
