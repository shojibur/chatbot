import { createApp, h } from 'vue';
import ChatWidget from './ChatWidget.vue';

(function () {
  const script = document.currentScript as HTMLScriptElement | null;
  if (!script) return;

  const clientCode = script.getAttribute('data-client-code');
  if (!clientCode) {
    console.error('[Davey] Missing data-client-code attribute on script tag.');
    return;
  }

  // Derive API base from the script src (same origin as the widget JS)
  const apiBase = script.getAttribute('data-api-base') || new URL(script.src).origin;

  // Create a shadow DOM host for style isolation
  const host = document.createElement('div');
  host.id = 'davey-chat-widget';
  document.body.appendChild(host);

  const shadow = host.attachShadow({ mode: 'open' });

  // Create a mount point inside shadow DOM
  const mountEl = document.createElement('div');
  shadow.appendChild(mountEl);

  // Fetch widget config then mount
  fetch(`${apiBase}/api/v1/widget-config/${clientCode}`, {
    headers: { Accept: 'application/json' },
  })
    .then((res) => {
      if (!res.ok) throw new Error(`Widget config fetch failed: ${res.status}`);
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
      console.error('[Davey] Failed to initialize widget:', err);
    });
})();
