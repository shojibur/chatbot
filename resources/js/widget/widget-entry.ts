import { createApp, h } from 'vue';
import ChatWidget from './ChatWidget.vue';
import widgetStyles from './widget-styles.css?inline';

(function () {
    // Support both sync and async loading by finding our script tag
    const script =
        (document.currentScript as HTMLScriptElement | null) ??
        (document.querySelector(
            'script[data-client-code]',
        ) as HTMLScriptElement | null);

    if (!script) {
        console.error(
            '[Zao Chat] Could not find widget script tag. Ensure it has a data-client-code attribute.',
        );
        return;
    }

    const clientCode = script.getAttribute('data-client-code');
    if (!clientCode) {
        console.error(
            '[Zao Chat] Missing data-client-code attribute on script tag.',
        );
        return;
    }

    const apiBase =
        script.getAttribute('data-api-base') || new URL(script.src).origin;

    // Create Shadow DOM host for full style isolation
    const host = document.createElement('div');
    host.id = 'davey-chat-widget';
    document.body.appendChild(host);

    const shadow = host.attachShadow({ mode: 'open' });

    // Inject styles into Shadow DOM (not <head>)
    const styleEl = document.createElement('style');
    styleEl.textContent = widgetStyles;
    shadow.appendChild(styleEl);

    // Create mount point inside Shadow DOM
    const mountEl = document.createElement('div');
    shadow.appendChild(mountEl);

    // Fetch config then mount
    fetch(`${apiBase}/api/v1/widget-config/${clientCode}`, {
        headers: { Accept: 'application/json' },
    })
        .then((res) => {
            if (!res.ok)
                throw new Error(`Widget config fetch failed: ${res.status}`);
            return res.json();
        })
        .then((config) => {
            const app = createApp({
                render: () =>
                    h(ChatWidget, {
                        clientCode,
                        apiBase,
                        config,
                    }),
            });

            app.mount(mountEl);
        })
        .catch((err) => {
            console.error('[Zao Chat] Failed to initialize widget:', err);
        });
})();
