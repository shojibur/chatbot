import { createApp, h } from 'vue';
import IframeChatWidget from './IframeChatWidget.vue';

(function () {
    const script =
        (document.currentScript as HTMLScriptElement | null) ??
        (document.querySelector(
            'script[data-client-code][data-mount-id]',
        ) as HTMLScriptElement | null);

    if (!script) {
        console.error(
            '[Davey iframe] Could not find iframe script tag. Ensure data-client-code is present.',
        );
        return;
    }

    const clientCode = script.getAttribute('data-client-code');
    if (!clientCode) {
        console.error('[Davey iframe] Missing data-client-code attribute.');
        return;
    }

    const mountId = script.getAttribute('data-mount-id') || 'davey-iframe-root';
    const mountEl = document.getElementById(mountId);
    if (!mountEl) {
        console.error(`[Davey iframe] Mount element #${mountId} not found.`);
        return;
    }

    const apiBase =
        script.getAttribute('data-api-base') || new URL(script.src).origin;
    const parentPageUrl = document.referrer || window.location.href;
    const iframeStyleOverride = script.getAttribute('data-iframe-style-override');
    const iframeThemeOverride = script.getAttribute('data-iframe-theme-override');

    const configUrl = new URL(`${apiBase}/api/v1/widget-config/${clientCode}`);
    configUrl.searchParams.set('page_url', parentPageUrl);

    fetch(configUrl.toString(), {
        headers: { Accept: 'application/json' },
    })
        .then((res) => {
            if (!res.ok)
                throw new Error(`Widget config fetch failed: ${res.status}`);
            return res.json();
        })
        .then((config) => {
            if (
                iframeStyleOverride &&
                ['classic', 'modern', 'glass'].includes(iframeStyleOverride)
            ) {
                config.widget_settings = config.widget_settings || {};
                config.widget_settings.iframe = config.widget_settings.iframe || {};
                config.widget_settings.iframe.widget_style = iframeStyleOverride;
            }

            if (
                iframeThemeOverride &&
                ['system', 'light', 'dark'].includes(iframeThemeOverride)
            ) {
                config.widget_settings = config.widget_settings || {};
                config.widget_settings.iframe = config.widget_settings.iframe || {};
                config.widget_settings.iframe.theme_mode = iframeThemeOverride;
            }

            createApp({
                render: () =>
                    h(IframeChatWidget, {
                        clientCode,
                        apiBase,
                        config,
                        parentPageUrl,
                    }),
            }).mount(mountEl);
        })
        .catch((err) => {
            console.error('[Davey iframe] Failed to initialize widget:', err);
        });
})();
